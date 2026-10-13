<?php
/**
 * API REST que usa el sitio: /wp-json/taquillapp/v1/…
 *
 * Seguridad:
 * - Los precios se calculan en el servidor a partir del catálogo.
 * - Cada pedido tiene una llave secreta (order_key) que solo conoce el comprador;
 *   sin ella no se puede consultar ni pagar el pedido.
 * - El estado "pagado" solo se fija con la respuesta de OpenPay/PayPal o con un
 *   webhook verificado contra la API, nunca con datos del navegador.
 */

defined( 'ABSPATH' ) || exit;

class TQC_Rest {

	const NS = 'taquillapp/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$public = '__return_true';
		$id     = '/orders/(?P<id>\d+)';

		register_rest_route( self::NS, '/products', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'products' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/orders', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_order' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, $id, array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_order' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, $id . '/pay/openpay', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'pay_openpay' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, $id . '/pay/paypal', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'paypal_create' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, $id . '/pay/paypal/capture', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'paypal_capture' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/webhooks/openpay', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'openpay_webhook' ), 'permission_callback' => $public ) );
	}

	/* ---------------- Helpers ---------------- */

	private static function error( $code, $message, $status = 400 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/** Pedido + llave válida, o error. */
	private static function order_from( WP_REST_Request $r ) {
		$o = TQC_Orders::get_with_key( $r['id'], (string) $r->get_param( 'order_key' ) );
		return $o ? $o : self::error( 'tq_order', 'No encontramos tu pedido.', 404 );
	}

	/** Solo se puede cobrar un pedido que no esté pagado ni en espera de pago. */
	private static function can_pay( array $o ) {
		if ( TQC_Orders::is_paid( $o['status'] ) ) {
			return self::error( 'tq_paid', 'Este pedido ya está pagado.', 409 );
		}
		if ( 'awaiting_payment' === $o['status'] ) {
			return self::error( 'tq_waiting', 'Este pedido ya tiene un pago en proceso.', 409 );
		}
		return true;
	}

	/** Límite simple por IP para evitar abuso al crear pedidos e intentar cobros. */
	private static function throttle( $bucket, $limit, $window ) {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'tqc_rl_' . $bucket . '_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= $limit ) {
			return self::error( 'tq_rate', 'Demasiados intentos. Espera unos minutos e intenta de nuevo.', 429 );
		}
		set_transient( $key, $n + 1, $window );
		return true;
	}

	private static function paid_response( $id ) {
		return TQC_Orders::public_view( TQC_Orders::get( $id ) );
	}

	/* ---------------- Catálogo y pedidos ---------------- */

	public static function products() {
		return rest_ensure_response( TQC_Products::catalog() );
	}

	public static function create_order( WP_REST_Request $r ) {
		$t = self::throttle( 'order', 30, 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $t ) ) {
			return $t;
		}
		$id = TQC_Orders::create( (array) $r->get_param( 'items' ), (array) $r->get_param( 'customer' ) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$o = TQC_Orders::get( $id );
		return rest_ensure_response(
			array(
				'order_id'  => $id,
				'order_key' => $o['key'],
				'total'     => $o['total'],
			)
		);
	}

	public static function get_order( WP_REST_Request $r ) {
		$o = self::order_from( $r );
		if ( is_wp_error( $o ) ) {
			return $o;
		}
		// Al volver de 3D Secure el webhook puede no haber llegado: se confirma con OpenPay.
		if ( 'awaiting_payment' === $o['status'] && 'openpay' === $o['gateway'] ) {
			TQC_Fulfillment::sync_openpay( $o['id'] );
			$o = TQC_Orders::get( $o['id'] );
		}
		return rest_ensure_response( TQC_Orders::public_view( $o ) );
	}

	/* ---------------- OpenPay ---------------- */

	public static function pay_openpay( WP_REST_Request $r ) {
		$o = self::order_from( $r );
		if ( is_wp_error( $o ) ) {
			return $o;
		}
		$ok = self::can_pay( $o );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( ! TQC_OpenPay::is_configured() ) {
			return self::error( 'tq_openpay', 'Este método de pago aún no está disponible.', 503 );
		}
		$t = self::throttle( 'pay', 15, 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $t ) ) {
			return $t;
		}

		if ( 'card' !== sanitize_key( (string) $r->get_param( 'method' ) ) ) {
			return self::error( 'tq_method', 'Método de pago no válido.' );
		}
		$token  = sanitize_text_field( (string) $r->get_param( 'token_id' ) );
		$device = sanitize_text_field( (string) $r->get_param( 'device_session_id' ) );
		if ( ! $token || ! $device ) {
			return self::error( 'tq_card', 'No pudimos validar tu tarjeta. Intenta de nuevo.' );
		}
		$charge = TQC_OpenPay::charge_card( $o, $token, $device );

		if ( is_wp_error( $charge ) ) {
			TQC_Orders::log( $o['id'], 'OpenPay rechazó el cargo: ' . $charge->get_error_message() );
			return $charge;
		}

		TQC_Orders::set_gateway( $o['id'], 'openpay', $charge['id'] ?? '' );
		$status = $charge['status'] ?? '';

		// Tarjeta aprobada al instante.
		if ( 'completed' === $status ) {
			TQC_Orders::mark_paid( $o['id'], 'Tarjeta aprobada (' . ( $charge['authorization'] ?? '' ) . ').' );
			return rest_ensure_response( self::paid_response( $o['id'] ) );
		}

		// Tarjeta que requiere 3D Secure: el banco valida y OpenPay regresa al sitio.
		$redirect = $charge['payment_method']['url'] ?? '';
		if ( $redirect ) {
			TQC_Orders::set_status( $o['id'], 'awaiting_payment', 'Esperando 3D Secure.' );
			return rest_ensure_response( array( 'status' => 'redirect', 'redirect_url' => esc_url_raw( $redirect ) ) );
		}

		TQC_Orders::set_status( $o['id'], 'failed', 'Estado OpenPay: ' . $status );
		return self::error( 'tq_card_declined', 'No pudimos procesar el pago. Intenta de nuevo o usa otro método.', 402 );
	}

	/**
	 * Webhook de OpenPay. Nunca se confía en el contenido: se vuelve a consultar el cargo en la API.
	 */
	public static function openpay_webhook( WP_REST_Request $r ) {
		$user = TQC_Settings::get( 'openpay_webhook_user' );
		$pass = TQC_Settings::get( 'openpay_webhook_pass' );
		if ( $user || $pass ) {
			$auth = $r->get_header( 'authorization' );
			$ok   = $auth && hash_equals( 'Basic ' . base64_encode( $user . ':' . $pass ), $auth ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			if ( ! $ok ) {
				return self::error( 'tq_auth', 'No autorizado.', 401 );
			}
		}

		$event = $r->get_json_params();
		$type  = $event['type'] ?? '';

		if ( 'verification' === $type ) {
			update_option( 'tqc_openpay_verification_code', sanitize_text_field( $event['verification_code'] ?? '' ), false );
			return rest_ensure_response( array( 'ok' => true ) );
		}

		$charge_id = sanitize_text_field( $event['transaction']['id'] ?? '' );
		if ( ! $charge_id ) {
			return rest_ensure_response( array( 'ok' => true ) );
		}
		$o = TQC_Orders::find_by_ref( 'openpay', $charge_id );
		if ( ! $o ) {
			return rest_ensure_response( array( 'ok' => true, 'ignored' => true ) );
		}

		$charge = TQC_OpenPay::get_charge( $charge_id );
		if ( is_wp_error( $charge ) ) {
			// 500 para que OpenPay reintente.
			return self::error( 'tq_openpay', 'No se pudo verificar el cargo.', 500 );
		}
		$status = $charge['status'] ?? '';
		if ( 'completed' === $status && abs( (float) $charge['amount'] - $o['total'] ) < 0.01 ) {
			TQC_Orders::mark_paid( $o['id'], 'Webhook OpenPay: ' . $type );
		} elseif ( in_array( $status, array( 'cancelled', 'failed', 'expired' ), true ) && ! TQC_Orders::is_paid( $o['status'] ) ) {
			TQC_Orders::set_status( $o['id'], 'failed' === $status ? 'failed' : 'expired', 'Webhook OpenPay: ' . $type );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}

	/* ---------------- PayPal ---------------- */

	public static function paypal_create( WP_REST_Request $r ) {
		$o = self::order_from( $r );
		if ( is_wp_error( $o ) ) {
			return $o;
		}
		$ok = self::can_pay( $o );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( ! TQC_PayPal::is_configured() ) {
			return self::error( 'tq_paypal', 'PayPal aún no está disponible.', 503 );
		}
		$res = TQC_PayPal::create_order( $o );
		if ( is_wp_error( $res ) ) {
			TQC_Orders::log( $o['id'], 'PayPal no creó la orden.' );
			return $res;
		}
		TQC_Orders::set_gateway( $o['id'], 'paypal', $res['id'] );
		TQC_Orders::log( $o['id'], 'Orden PayPal creada: ' . $res['id'] );
		return rest_ensure_response( array( 'paypal_order_id' => $res['id'] ) );
	}

	public static function paypal_capture( WP_REST_Request $r ) {
		$o = self::order_from( $r );
		if ( is_wp_error( $o ) ) {
			return $o;
		}
		if ( TQC_Orders::is_paid( $o['status'] ) ) {
			return rest_ensure_response( TQC_Orders::public_view( $o ) );
		}
		$pp_id = sanitize_text_field( (string) $r->get_param( 'paypal_order_id' ) );
		if ( 'paypal' !== $o['gateway'] || ! $pp_id || ! hash_equals( (string) $o['gateway_ref'], $pp_id ) ) {
			return self::error( 'tq_paypal_mismatch', 'El pago de PayPal no corresponde a este pedido.', 400 );
		}
		$res = TQC_PayPal::capture( $pp_id, $o['id'] );
		if ( is_wp_error( $res ) ) {
			TQC_Orders::log( $o['id'], 'PayPal no capturó el pago.' );
			return $res;
		}
		$capture = TQC_PayPal::completed_capture( $res, $o );
		if ( ! $capture ) {
			TQC_Orders::set_status( $o['id'], 'failed', 'PayPal: captura no completada.' );
			return self::error( 'tq_paypal_capture', 'PayPal no confirmó el pago. No se hizo ningún cargo.', 402 );
		}
		update_post_meta( $o['id'], '_tq_paypal_capture_id', sanitize_text_field( $capture['id'] ) );
		TQC_Orders::mark_paid( $o['id'], 'PayPal captura ' . $capture['id'] );
		return rest_ensure_response( self::paid_response( $o['id'] ) );
	}
}
