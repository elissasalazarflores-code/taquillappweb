<?php
/**
 * Correos al comprador (códigos e instrucciones de pago) y alertas internas.
 * Para cambiar el diseño, usa el filtro "tqc_email_html".
 */

defined( 'ABSPATH' ) || exit;

class TQC_Emails {

	private static function send( $to, $subject, $title, $body_html ) {
		$html = '<!doctype html><html><body style="margin:0;background:#F2E8D9;font-family:Helvetica,Arial,sans-serif;color:#0D0F1C">'
			. '<div style="max-width:560px;margin:0 auto;padding:32px 20px">'
			. '<div style="background:#0D0F1C;border-radius:20px 20px 0 0;padding:24px 28px;color:#F2E8D9;font-weight:800;font-size:22px">taquill<span style="color:#FF5C4D">app</span></div>'
			. '<div style="height:6px;background:linear-gradient(90deg,#F07833,#FF5C4D,#FF2D78)"></div>'
			. '<div style="background:#ffffff;border-radius:0 0 20px 20px;padding:28px">'
			. '<h1 style="font-size:22px;margin:0 0 12px">' . esc_html( $title ) . '</h1>'
			. $body_html
			. '</div>'
			. '<p style="font-size:12px;color:#6b6b76;text-align:center;margin-top:18px">TaquillApp es una plataforma independiente. Cinépolis y Cinemex son marcas de sus respectivos titulares.</p>'
			. '</div></body></html>';
		$html = apply_filters( 'tqc_email_html', $html, $subject, $title, $body_html );
		return wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	public static function codes( $id ) {
		$o = TQC_Orders::get( $id );
		if ( ! $o ) {
			return;
		}
		$rows = '';
		foreach ( $o['items'] as $line ) {
			foreach ( (array) ( $line['codes'] ?? array() ) as $code ) {
				$rows .= '<tr><td style="padding:12px 0;border-bottom:1px dashed #FF5C4D"><div style="font-size:13px;color:#6b6b76">' . esc_html( $line['name'] . ' · ' . $line['chain'] ) . '</div>'
					. '<div style="font-size:20px;font-weight:800;letter-spacing:2px">' . esc_html( $code ) . '</div></td></tr>';
			}
		}
		$body = '<p>Hola ' . esc_html( $o['customer']['name'] ) . ', aquí están tus códigos del pedido #' . (int) $id . '.</p>'
			. '<table style="width:100%;border-collapse:collapse">' . $rows . '</table>'
			. '<p style="font-size:14px;color:#6b6b76;margin-top:18px">Canjea los boletos en la web o app oficial de tu cine o en taquilla. Los combos se canjean en dulcería. Revisa la vigencia y condiciones de cada producto.</p>';
		self::send( $o['customer']['email'], 'Tus códigos de TaquillApp · Pedido #' . $id, 'Tus códigos están listos', $body );
	}

	public static function instructions( $id ) {
		$o = TQC_Orders::get( $id );
		if ( ! $o || empty( $o['instructions'] ) ) {
			return;
		}
		$i    = $o['instructions'];
		$row  = function ( $k, $v ) {
			return $v ? '<tr><td style="padding:6px 0;color:#6b6b76">' . esc_html( $k ) . '</td><td style="padding:6px 0;text-align:right;font-weight:700">' . esc_html( $v ) . '</td></tr>' : '';
		};
		$body = '<p>Hola ' . esc_html( $o['customer']['name'] ) . ', tu pedido #' . (int) $id . ' está apartado. Completa el pago con estos datos:</p>'
			. '<table style="width:100%;border-collapse:collapse">'
			. $row( 'Banco', $i['bank'] ?? '' ) . $row( 'CLABE', $i['clabe'] ?? '' ) . $row( 'Referencia', $i['reference'] ?? '' )
			. $row( 'Monto', '$' . number_format( $o['total'], 2 ) . ' MXN' ) . $row( 'Paga antes de', $i['due_date'] ?? '' )
			. '</table>'
			. ( ! empty( $i['receipt_url'] ) ? '<p><a href="' . esc_url( $i['receipt_url'] ) . '" style="color:#FF2D78;font-weight:700">Descargar ficha de pago</a></p>' : '' )
			. '<p style="font-size:14px;color:#6b6b76">En cuanto se confirme tu pago te enviamos tus códigos a este correo.</p>';
		$subject = 'spei' === ( $i['type'] ?? '' ) ? 'Datos para tu transferencia' : 'Tu ficha para pagar en tienda';
		self::send( $o['customer']['email'], $subject . ' · Pedido #' . $id, $subject, $body );
	}

	public static function admin_alert( $id, array $errors ) {
		$to = TQC_Settings::get( 'notify_email' );
		$to = $to ? $to : get_option( 'admin_email' );
		$body = '<p>El pedido #' . (int) $id . ' está pagado, pero no se pudieron emitir todos los códigos. El sistema lo reintentará cada 15 minutos.</p>'
			. '<p><strong>Detalle:</strong><br>' . implode( '<br>', array_map( 'esc_html', $errors ) ) . '</p>'
			. '<p><a href="' . esc_url( admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) ) . '">Ver pedido</a></p>';
		self::send( $to, '[TaquillApp] Falta emitir códigos · Pedido #' . $id, 'Pedido pagado sin códigos', $body );
	}
}
