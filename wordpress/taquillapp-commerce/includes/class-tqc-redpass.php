<?php
/**
 * Conector con RedPass (emisión de códigos oficiales de Cinépolis y Cinemex).
 *
 * ⚠ PENDIENTE DE AJUSTAR A LA ESPECIFICACIÓN DE REDPASS ⚠
 * No contamos con la documentación de la API de RedPass. Este archivo es el ÚNICO
 * lugar que hay que adaptar cuando la entreguen: ruta, autenticación, cuerpo y la
 * forma de la respuesta. Todo lo demás (pedidos, reintentos, correos) ya usa la
 * función issue() y no cambia.
 *
 * Supuesto actual (a confirmar con RedPass):
 *   POST {base_url}/codes
 *   Authorization: Bearer {api_key}
 *   { "sku": "...", "quantity": 2, "reference": "TQ123-0", "customer_email": "..." }
 *   → 200 { "codes": [ { "code": "ABC123" }, ... ] }
 *
 * "reference" es única por línea de pedido para que RedPass pueda evitar duplicados
 * si un reintento llega dos veces.
 *
 * También se puede reemplazar sin tocar este archivo con el filtro:
 *   add_filter( 'tqc_redpass_issue', function ( $codes, $line, $qty, $reference, $order ) { ... }, 10, 5 );
 */

defined( 'ABSPATH' ) || exit;

class TQC_RedPass {

	public static function is_configured() {
		return (bool) ( TQC_Settings::get( 'redpass_base_url' ) && TQC_Settings::get( 'redpass_api_key' ) );
	}

	/**
	 * Emite $qty códigos para una línea del pedido.
	 *
	 * @return string[]|WP_Error Lista de códigos.
	 */
	public static function issue( array $line, $qty, $reference, array $order ) {
		$custom = apply_filters( 'tqc_redpass_issue', null, $line, $qty, $reference, $order );
		if ( null !== $custom ) {
			return $custom;
		}

		if ( ! self::is_configured() ) {
			// En pruebas sin RedPass se generan códigos de ejemplo para poder probar el flujo completo.
			if ( TQC_Settings::is_sandbox() ) {
				$codes = array();
				for ( $i = 0; $i < $qty; $i++ ) {
					$codes[] = 'PRUEBA-' . strtoupper( wp_generate_password( 8, false ) );
				}
				return $codes;
			}
			return new WP_Error( 'redpass_config', 'RedPass no está configurado.' );
		}
		if ( empty( $line['redpass_sku'] ) ) {
			return new WP_Error( 'redpass_sku', sprintf( 'El producto "%s" no tiene SKU de RedPass.', $line['name'] ) );
		}

		$headers = array(
			'Authorization' => 'Bearer ' . TQC_Settings::get( 'redpass_api_key' ),
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		);
		if ( TQC_Settings::get( 'redpass_client_id' ) ) {
			$headers['X-Client-Id'] = TQC_Settings::get( 'redpass_client_id' );
		}

		$res = wp_remote_post(
			TQC_Settings::get( 'redpass_base_url' ) . '/codes',
			array(
				'timeout' => 45,
				'headers' => $headers,
				'body'    => wp_json_encode(
					array(
						'sku'            => $line['redpass_sku'],
						'quantity'       => (int) $qty,
						'reference'      => $reference,
						'customer_email' => $order['customer']['email'] ?? '',
					)
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'redpass_http', 'Sin conexión con RedPass: ' . $res->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 400 || ! is_array( $data ) ) {
			return new WP_Error( 'redpass_' . $code, sprintf( 'RedPass respondió %d: %s', $code, wp_strip_all_tags( substr( wp_remote_retrieve_body( $res ), 0, 300 ) ) ) );
		}

		$codes = array();
		foreach ( (array) ( $data['codes'] ?? array() ) as $c ) {
			$value = is_array( $c ) ? ( $c['code'] ?? '' ) : $c;
			if ( is_string( $value ) && '' !== $value ) {
				$codes[] = sanitize_text_field( $value );
			}
		}
		if ( count( $codes ) !== (int) $qty ) {
			return new WP_Error( 'redpass_count', sprintf( 'RedPass entregó %d de %d códigos.', count( $codes ), $qty ) );
		}
		return $codes;
	}
}
