<?php
/**
 * Pedidos (tipo de contenido privado "tq_order").
 *
 * Estados (_tq_status):
 *   pending             creado, sin pago
 *   awaiting_payment    3D Secure en curso (el banco aún no confirma)
 *   paid                pagado, emitiendo códigos
 *   fulfilled           pagado y con todos los códigos emitidos
 *   fulfillment_failed  pagado, pero RedPass falló (se reintenta solo)
 *   failed              pago rechazado
 */

defined( 'ABSPATH' ) || exit;

class TQC_Orders {

	const CPT     = 'tq_order';
	const MAX_QTY = 20;

	const STATUSES = array(
		'pending'            => 'Pendiente',
		'awaiting_payment'   => 'Esperando pago',
		'paid'               => 'Pagado',
		'fulfilled'          => 'Completado',
		'fulfillment_failed' => 'Pagado · falta emitir códigos',
		'failed'             => 'Pago rechazado',
		'expired'            => 'Vencido',
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => 'Pedidos TaquillApp',
					'singular_name' => 'Pedido',
					'menu_name'     => 'Pedidos',
					'edit_item'     => 'Pedido',
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-cart',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Crea un pedido a partir del carrito. Precios y existencia se validan aquí, no en el navegador.
	 *
	 * @return int|WP_Error
	 */
	public static function create( array $items, array $customer ) {
		$lines = array();
		$total = 0;
		foreach ( $items as $it ) {
			$sku = isset( $it['id'] ) ? sanitize_key( $it['id'] ) : '';
			$qty = isset( $it['qty'] ) ? (int) $it['qty'] : 0;
			$p   = $sku ? TQC_Products::find( $sku ) : null;
			if ( ! $p || $p['price'] <= 0 ) {
				return new WP_Error( 'tq_product', 'Uno de los productos ya no está disponible. Actualiza la página e intenta de nuevo.', array( 'status' => 400 ) );
			}
			if ( $qty < 1 || $qty > self::MAX_QTY ) {
				return new WP_Error( 'tq_qty', 'Cantidad no válida.', array( 'status' => 400 ) );
			}
			$lines[] = array(
				'sku'         => $p['id'],
				'name'        => $p['name'],
				'chain'       => TQC_Products::CHAINS[ $p['chain'] ] ?? $p['chain'],
				'days'        => $p['days'],
				'qty'         => $qty,
				'unit_price'  => $p['price'],
				'redpass_sku' => $p['redpass_sku'],
				'codes'       => array(),
			);
			$total += $p['price'] * $qty;
		}
		if ( ! $lines ) {
			return new WP_Error( 'tq_empty', 'Tu carrito está vacío.', array( 'status' => 400 ) );
		}

		$c = array(
			'name'      => sanitize_text_field( $customer['name'] ?? '' ),
			'last_name' => sanitize_text_field( $customer['last_name'] ?? '' ),
			'email'     => sanitize_email( $customer['email'] ?? '' ),
			'phone'     => preg_replace( '/\D/', '', (string) ( $customer['phone'] ?? '' ) ),
		);
		if ( ! $c['name'] || ! $c['last_name'] || ! is_email( $c['email'] ) ) {
			return new WP_Error( 'tq_customer', 'Revisa tu nombre y correo.', array( 'status' => 400 ) );
		}

		$id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => 'Pedido',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		wp_update_post( array( 'ID' => $id, 'post_title' => sprintf( 'Pedido #%d — %s', $id, $c['email'] ) ) );

		update_post_meta( $id, '_tq_key', wp_generate_password( 32, false ) );
		update_post_meta( $id, '_tq_items', $lines );
		update_post_meta( $id, '_tq_total', round( $total, 2 ) );
		update_post_meta( $id, '_tq_customer', $c );
		update_post_meta( $id, '_tq_status', 'pending' );
		update_post_meta( $id, '_tq_mode', TQC_Settings::is_sandbox() ? 'sandbox' : 'live' );
		self::log( $id, 'Pedido creado.' );

		return $id;
	}

	/* ---------------- Lectura ---------------- */

	public static function get( $id ) {
		$id = (int) $id;
		if ( ! $id || self::CPT !== get_post_type( $id ) ) {
			return null;
		}
		return array(
			'id'           => $id,
			'key'          => get_post_meta( $id, '_tq_key', true ),
			'status'       => get_post_meta( $id, '_tq_status', true ),
			'items'        => (array) get_post_meta( $id, '_tq_items', true ),
			'total'        => (float) get_post_meta( $id, '_tq_total', true ),
			'customer'     => (array) get_post_meta( $id, '_tq_customer', true ),
			'gateway'      => get_post_meta( $id, '_tq_gateway', true ),
			'gateway_ref'  => get_post_meta( $id, '_tq_gateway_ref', true ),
			'mode'         => get_post_meta( $id, '_tq_mode', true ),
		);
	}

	/** Busca un pedido verificando su llave secreta (la que recibe el comprador). */
	public static function get_with_key( $id, $key ) {
		$o = self::get( $id );
		if ( ! $o || ! is_string( $key ) || ! hash_equals( (string) $o['key'], $key ) ) {
			return null;
		}
		return $o;
	}

	public static function find_by_ref( $gateway, $ref ) {
		$ids = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_tq_gateway', 'value' => $gateway ),
					array( 'key' => '_tq_gateway_ref', 'value' => $ref ),
				),
			)
		);
		return $ids ? self::get( $ids[0] ) : null;
	}

	public static function is_paid( $status ) {
		return in_array( $status, array( 'paid', 'fulfilled', 'fulfillment_failed' ), true );
	}

	/** Lo que se puede mostrar al comprador. */
	public static function public_view( array $o ) {
		$codes = array();
		foreach ( $o['items'] as $line ) {
			foreach ( (array) ( $line['codes'] ?? array() ) as $code ) {
				$codes[] = array( 'product' => $line['name'], 'chain' => $line['chain'], 'code' => $code );
			}
		}
		$status = $o['status'];
		if ( self::is_paid( $status ) ) {
			$public = 'paid';
		} elseif ( 'awaiting_payment' === $status ) {
			$public = 'processing'; // 3D Secure: el banco aún no confirma.
		} elseif ( in_array( $status, array( 'failed', 'expired' ), true ) ) {
			$public = 'failed';
		} else {
			$public = 'created';
		}
		return array(
			'order_id'     => $o['id'],
			'status'       => $public,
			'total'        => $o['total'],
			'email'        => $o['customer']['email'] ?? '',
			'codes'        => $codes,
			'codes_ready'  => 'fulfilled' === $status,
		);
	}

	/* ---------------- Escritura ---------------- */

	public static function set_status( $id, $status, $note = '' ) {
		update_post_meta( $id, '_tq_status', $status );
		self::log( $id, sprintf( 'Estado: %s.%s', self::STATUSES[ $status ] ?? $status, $note ? ' ' . $note : '' ) );
	}

	public static function set_gateway( $id, $gateway, $ref ) {
		update_post_meta( $id, '_tq_gateway', $gateway );
		update_post_meta( $id, '_tq_gateway_ref', $ref );
	}

	/**
	 * Marca como pagado (idempotente) y dispara la emisión de códigos.
	 * Lo llaman la respuesta directa de OpenPay/PayPal y los webhooks; puede llegar dos veces.
	 */
	public static function mark_paid( $id, $note = '' ) {
		$o = self::get( $id );
		if ( ! $o || self::is_paid( $o['status'] ) ) {
			return;
		}
		update_post_meta( $id, '_tq_paid_at', current_time( 'mysql' ) );
		self::set_status( $id, 'paid', $note );
		TQC_Fulfillment::fulfill( $id );
	}

	public static function log( $id, $message ) {
		$log   = (array) get_post_meta( $id, '_tq_log', true );
		$log[] = array( 'at' => current_time( 'mysql' ), 'msg' => $message );
		update_post_meta( $id, '_tq_log', array_slice( array_filter( $log ), -100 ) );
	}
}
