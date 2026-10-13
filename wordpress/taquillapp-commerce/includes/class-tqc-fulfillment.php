<?php
/**
 * Emisión de códigos después del pago, con reintentos automáticos.
 *
 * - fulfill() pide a RedPass solo los códigos que faltan, línea por línea, así un
 *   reintento nunca duplica códigos ya emitidos.
 * - Un candado evita que el webhook y la respuesta directa emitan al mismo tiempo.
 * - Cada 15 minutos el cron reintenta pedidos pagados sin códigos y revisa con
 *   OpenPay los pagos con 3D Secure pendientes (por si un webhook no llegó).
 */

defined( 'ABSPATH' ) || exit;

class TQC_Fulfillment {

	const CRON         = 'tqc_cron';
	const MAX_ATTEMPTS = 24;

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::CRON, array( __CLASS__, 'cron' ) );
	}

	public static function schedules( $s ) {
		$s['tqc_15min'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Cada 15 minutos (TaquillApp)' );
		return $s;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + 300, 'tqc_15min', self::CRON );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/** @return bool true si el pedido quedó con todos sus códigos. */
	public static function fulfill( $id ) {
		$lock = 'tqc_lock_' . $id;
		if ( ! add_option( $lock, time(), '', 'no' ) ) {
			// Otro proceso está emitiendo. Si el candado es viejo (proceso muerto), se libera.
			if ( (int) get_option( $lock ) < time() - 300 ) {
				delete_option( $lock );
			}
			return false;
		}

		try {
			$o = TQC_Orders::get( $id );
			if ( ! $o || ! TQC_Orders::is_paid( $o['status'] ) || 'fulfilled' === $o['status'] ) {
				return $o && 'fulfilled' === $o['status'];
			}

			$items  = $o['items'];
			$errors = array();
			foreach ( $items as $i => $line ) {
				$have    = count( (array) ( $line['codes'] ?? array() ) );
				$missing = (int) $line['qty'] - $have;
				if ( $missing <= 0 ) {
					continue;
				}
				$reference = sprintf( 'TQ%d-%d-%d', $id, $i, $have );
				$codes     = TQC_RedPass::issue( $line, $missing, $reference, $o );
				if ( is_wp_error( $codes ) ) {
					$errors[] = $codes->get_error_message();
					continue;
				}
				$items[ $i ]['codes'] = array_merge( (array) ( $line['codes'] ?? array() ), $codes );
				// Se guarda línea por línea: si algo falla después, estos códigos no se pierden.
				update_post_meta( $id, '_tq_items', $items );
			}

			if ( $errors ) {
				$attempts = (int) get_post_meta( $id, '_tq_fulfill_attempts', true ) + 1;
				update_post_meta( $id, '_tq_fulfill_attempts', $attempts );
				$first_failure = 'fulfillment_failed' !== $o['status'];
				TQC_Orders::set_status( $id, 'fulfillment_failed', sprintf( 'Intento %d: %s', $attempts, implode( ' | ', $errors ) ) );
				if ( $first_failure ) {
					TQC_Emails::admin_alert( $id, $errors );
				}
				return false;
			}

			TQC_Orders::set_status( $id, 'fulfilled', 'Códigos emitidos.' );
			TQC_Emails::codes( $id );
			return true;
		} finally {
			delete_option( $lock );
		}
	}

	public static function cron() {
		// 1) Reintentar emisiones fallidas.
		$failed = get_posts(
			array(
				'post_type'      => TQC_Orders::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_tq_status', 'value' => 'fulfillment_failed' ),
					array( 'key' => '_tq_fulfill_attempts', 'value' => self::MAX_ATTEMPTS, 'compare' => '<', 'type' => 'NUMERIC' ),
				),
			)
		);
		// Pedidos pagados que se quedaron sin emitir (por ejemplo, el proceso se cortó).
		$stuck = get_posts(
			array(
				'post_type'      => TQC_Orders::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_tq_status', 'value' => 'paid' ),
					array( 'key' => '_tq_paid_at', 'value' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 5 * MINUTE_IN_SECONDS ), 'compare' => '<', 'type' => 'DATETIME' ),
				),
			)
		);
		foreach ( array_merge( $failed, $stuck ) as $id ) {
			self::fulfill( $id );
		}

		// 2) Revisar con OpenPay los pagos con 3D Secure pendientes.
		if ( ! TQC_OpenPay::is_configured() ) {
			return;
		}
		$waiting = get_posts(
			array(
				'post_type'      => TQC_Orders::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 30,
				'fields'         => 'ids',
				'date_query'     => array( array( 'after' => '10 days ago' ) ),
				'meta_query'     => array(
					array( 'key' => '_tq_status', 'value' => 'awaiting_payment' ),
					array( 'key' => '_tq_gateway', 'value' => 'openpay' ),
				),
			)
		);
		foreach ( $waiting as $id ) {
			self::sync_openpay( $id );
		}
	}

	/** Consulta el cargo en OpenPay y actualiza el pedido. */
	public static function sync_openpay( $id ) {
		$o = TQC_Orders::get( $id );
		if ( ! $o || 'openpay' !== $o['gateway'] || ! $o['gateway_ref'] || 'awaiting_payment' !== $o['status'] ) {
			return;
		}
		$charge = TQC_OpenPay::get_charge( $o['gateway_ref'] );
		if ( is_wp_error( $charge ) ) {
			return;
		}
		$status = $charge['status'] ?? '';
		if ( 'completed' === $status && abs( (float) $charge['amount'] - $o['total'] ) < 0.01 ) {
			TQC_Orders::mark_paid( $id, 'Confirmado con OpenPay.' );
		} elseif ( in_array( $status, array( 'cancelled', 'failed', 'expired' ), true ) ) {
			TQC_Orders::set_status( $id, 'failed' === $status ? 'failed' : 'expired', 'OpenPay: ' . $status );
		}
	}
}
