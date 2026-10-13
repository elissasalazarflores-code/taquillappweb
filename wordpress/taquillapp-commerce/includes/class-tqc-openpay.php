<?php
/**
 * Cliente de la API de OpenPay (México).
 * Docs: https://documents.openpay.mx/docs/api/
 *
 * La tarjeta se tokeniza en el navegador con OpenPay.js; aquí solo se usa el token.
 */

defined( 'ABSPATH' ) || exit;

class TQC_OpenPay {

	public static function is_configured() {
		return TQC_Settings::get( 'openpay_merchant_id' ) && TQC_Settings::get( 'openpay_private_key' ) && TQC_Settings::get( 'openpay_public_key' );
	}

	private static function base() {
		$host = TQC_Settings::is_sandbox() ? 'https://sandbox-api.openpay.mx' : 'https://api.openpay.mx';
		return $host . '/v1/' . rawurlencode( TQC_Settings::get( 'openpay_merchant_id' ) );
	}

	/**
	 * @return array|WP_Error
	 */
	private static function request( $method, $path, $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 45,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( TQC_Settings::get( 'openpay_private_key' ) . ':' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( self::base() . $path, $args );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'openpay_http', 'No pudimos conectar con el procesador de pagos. Intenta de nuevo.', array( 'status' => 502 ) );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 400 ) {
			$err = is_array( $data ) ? $data : array();
			return new WP_Error( 'openpay_' . ( $err['error_code'] ?? $code ), self::message( $err ), array( 'status' => 402, 'openpay' => $err ) );
		}
		return is_array( $data ) ? $data : array();
	}

	/** Mensajes claros para los códigos de error más comunes. */
	private static function message( array $err ) {
		$map = array(
			1006 => 'Este pago ya se había registrado.',
			2004 => 'El número de tarjeta no es válido.',
			2005 => 'La fecha de vencimiento ya pasó.',
			2006 => 'Falta el código de seguridad (CVV).',
			2009 => 'El código de seguridad no es válido.',
			3001 => 'Tu banco rechazó la tarjeta. Prueba con otra.',
			3002 => 'La tarjeta está vencida.',
			3003 => 'La tarjeta no tiene fondos suficientes.',
			3004 => 'La tarjeta fue reportada como robada. Prueba con otra.',
			3005 => 'El pago fue rechazado por seguridad. Prueba con otra tarjeta.',
			3006 => 'Tu banco no permite esta operación.',
			3009 => 'La tarjeta fue reportada como perdida. Prueba con otra.',
			3010 => 'Tu banco restringió la tarjeta.',
			3011 => 'Tu banco pidió retener la tarjeta. Prueba con otra.',
			3012 => 'Tu banco pide autorizar este pago. Llámales o prueba con otra tarjeta.',
		);
		$code = isset( $err['error_code'] ) ? (int) $err['error_code'] : 0;
		return $map[ $code ] ?? 'No pudimos procesar el pago. Intenta de nuevo o usa otro método.';
	}

	private static function customer( array $o ) {
		$c = $o['customer'];
		$out = array(
			'name'      => $c['name'],
			'last_name' => $c['last_name'],
			'email'     => $c['email'],
		);
		if ( ! empty( $c['phone'] ) ) {
			$out['phone_number'] = $c['phone'];
		}
		return $out;
	}

	private static function base_charge( array $o ) {
		return array(
			'amount'      => round( $o['total'], 2 ),
			'currency'    => 'MXN',
			'description' => sprintf( 'TaquillApp pedido #%d', $o['id'] ),
			// order_id debe ser único por intento; el prefijo permite ubicar el pedido.
			'order_id'    => sprintf( 'TQ%d-%s', $o['id'], strtolower( wp_generate_password( 6, false ) ) ),
			'customer'    => self::customer( $o ),
		);
	}

	/** Cargo con tarjeta (token de OpenPay.js). */
	public static function charge_card( array $o, $token, $device_session_id ) {
		$body = self::base_charge( $o ) + array(
			'method'            => 'card',
			'source_id'         => $token,
			'device_session_id' => $device_session_id,
		);
		$tds = TQC_Settings::get( 'openpay_3ds' );
		if ( 'off' !== $tds ) {
			$body['redirect_url'] = add_query_arg( array( 'tq_order' => $o['id'], 'tq_key' => $o['key'] ), home_url( '/' ) );
			if ( 'force' === $tds ) {
				$body['use_3d_secure'] = true;
			}
		}
		return self::request( 'POST', '/charges', $body );
	}

	/** Ficha SPEI (CLABE). */
	public static function charge_spei( array $o ) {
		return self::request( 'POST', '/charges', self::base_charge( $o ) + array( 'method' => 'bank_account', 'due_date' => self::due_date() ) );
	}

	/** Referencia para pago en tienda (Paynet). */
	public static function charge_store( array $o ) {
		return self::request( 'POST', '/charges', self::base_charge( $o ) + array( 'method' => 'store', 'due_date' => self::due_date() ) );
	}

	public static function get_charge( $id ) {
		return self::request( 'GET', '/charges/' . rawurlencode( $id ) );
	}

	private static function due_date() {
		$hours = max( 1, (int) TQC_Settings::get( 'offline_hours' ) );
		return gmdate( 'Y-m-d\TH:i:s', time() + $hours * HOUR_IN_SECONDS );
	}

	/** Datos para que el comprador pague por SPEI o en tienda. */
	public static function instructions( array $charge ) {
		$pm = $charge['payment_method'] ?? array();
		if ( 'bank_transfer' === ( $pm['type'] ?? '' ) ) {
			return array(
				'type'      => 'spei',
				'bank'      => $pm['bank'] ?? '',
				'clabe'     => $pm['clabe'] ?? '',
				'reference' => $pm['name'] ?? '',
				'amount'    => (float) ( $charge['amount'] ?? 0 ),
				'due_date'  => self::format_date( $charge['due_date'] ?? '' ),
			);
		}
		$dashboard = TQC_Settings::is_sandbox() ? 'https://sandbox-dashboard.openpay.mx' : 'https://dashboard.openpay.mx';
		$reference = $pm['reference'] ?? '';
		return array(
			'type'        => 'store',
			'reference'   => $reference,
			'barcode_url' => $pm['barcode_url'] ?? '',
			'receipt_url' => $reference ? sprintf( '%s/paynet-pdf/%s/%s', $dashboard, rawurlencode( TQC_Settings::get( 'openpay_merchant_id' ) ), rawurlencode( $reference ) ) : '',
			'amount'      => (float) ( $charge['amount'] ?? 0 ),
			'due_date'    => self::format_date( $charge['due_date'] ?? '' ),
		);
	}

	private static function format_date( $iso ) {
		$ts = $iso ? strtotime( $iso ) : false;
		return $ts ? wp_date( 'j \d\e F, H:i \h', $ts ) : '';
	}
}
