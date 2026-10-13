<?php
/**
 * Cliente de PayPal (Orders v2).
 * Docs: https://developer.paypal.com/docs/api/orders/v2/
 *
 * El navegador muestra los botones oficiales; la orden se crea y se captura aquí,
 * así el monto siempre sale del pedido guardado en WordPress.
 */

defined( 'ABSPATH' ) || exit;

class TQC_PayPal {

	public static function is_configured() {
		return TQC_Settings::get( 'paypal_client_id' ) && TQC_Settings::get( 'paypal_secret' );
	}

	private static function base() {
		return TQC_Settings::is_sandbox() ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
	}

	/** @return string|WP_Error */
	private static function token() {
		$cache_key = 'tqc_paypal_token_' . md5( TQC_Settings::get( 'paypal_client_id' ) . self::base() );
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}
		$res = wp_remote_post(
			self::base() . '/v1/oauth2/token',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( TQC_Settings::get( 'paypal_client_id' ) . ':' . TQC_Settings::get( 'paypal_secret' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => 'grant_type=client_credentials',
			)
		);
		$data = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'paypal_auth', 'PayPal no está disponible en este momento.', array( 'status' => 502 ) );
		}
		set_transient( $cache_key, $data['access_token'], max( 60, (int) $data['expires_in'] - 120 ) );
		return $data['access_token'];
	}

	/** @return array|WP_Error */
	private static function request( $method, $path, $body = null, $idempotency_key = '' ) {
		$token = self::token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$headers = array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		);
		if ( $idempotency_key ) {
			$headers['PayPal-Request-Id'] = $idempotency_key;
		}
		$res = wp_remote_request(
			self::base() . $path,
			array(
				'method'  => $method,
				'timeout' => 45,
				'headers' => $headers,
				'body'    => null === $body ? null : wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'paypal_http', 'No pudimos conectar con PayPal. Intenta de nuevo.', array( 'status' => 502 ) );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 400 ) {
			return new WP_Error( 'paypal_' . $code, 'PayPal no pudo procesar el pago. Intenta de nuevo o usa otro método.', array( 'status' => 402, 'paypal' => $data ) );
		}
		return is_array( $data ) ? $data : array();
	}

	public static function create_order( array $o ) {
		$amount = number_format( $o['total'], 2, '.', '' );
		return self::request(
			'POST',
			'/v2/checkout/orders',
			array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'reference_id' => 'tq-' . $o['id'],
						'custom_id'    => (string) $o['id'],
						'description'  => sprintf( 'TaquillApp pedido #%d', $o['id'] ),
						'amount'       => array( 'currency_code' => 'MXN', 'value' => $amount ),
					),
				),
				'payment_source' => array(
					'paypal' => array(
						'experience_context' => array(
							'brand_name'          => 'TaquillApp',
							'locale'              => 'es-MX',
							'shipping_preference' => 'NO_SHIPPING',
							'user_action'         => 'PAY_NOW',
						),
					),
				),
			),
			'tq-create-' . $o['id'] . '-' . wp_generate_password( 8, false )
		);
	}

	public static function capture( $paypal_order_id, $order_id ) {
		return self::request( 'POST', '/v2/checkout/orders/' . rawurlencode( $paypal_order_id ) . '/capture', new stdClass(), 'tq-capture-' . $order_id . '-' . $paypal_order_id );
	}

	/** Devuelve la captura completada si el monto coincide con el pedido; si no, null. */
	public static function completed_capture( array $result, array $o ) {
		if ( 'COMPLETED' !== ( $result['status'] ?? '' ) ) {
			return null;
		}
		$capture = $result['purchase_units'][0]['payments']['captures'][0] ?? null;
		if ( ! $capture || 'COMPLETED' !== ( $capture['status'] ?? '' ) ) {
			return null;
		}
		$amount   = $capture['amount']['value'] ?? '';
		$currency = $capture['amount']['currency_code'] ?? '';
		if ( 'MXN' !== $currency || abs( (float) $amount - $o['total'] ) > 0.009 ) {
			return null;
		}
		return $capture;
	}
}
