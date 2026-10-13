<?php
/**
 * Ajustes del plugin: Ajustes → TaquillApp.
 *
 * Cada ajuste se puede fijar también como constante en wp-config.php
 * (recomendado para llaves privadas), por ejemplo:
 *   define( 'TQC_OPENPAY_PRIVATE_KEY', 'sk_...' );
 * La constante siempre gana sobre lo guardado en la base de datos.
 */

defined( 'ABSPATH' ) || exit;

class TQC_Settings {

	const OPTION = 'tqc_settings';

	public static function fields() {
		return array(
			'general' => array(
				'title'  => 'General',
				'fields' => array(
					'mode'            => array( 'label' => 'Modo', 'type' => 'select', 'options' => array( 'sandbox' => 'Pruebas (sandbox)', 'live' => 'Producción' ), 'default' => 'sandbox' ),
					'notify_email'    => array( 'label' => 'Correo para alertas', 'type' => 'email', 'help' => 'Recibe avisos cuando falle la emisión de códigos.' ),
					'offline_hours'   => array( 'label' => 'Horas para pagar SPEI / tienda', 'type' => 'number', 'default' => 48 ),
				),
			),
			'openpay' => array(
				'title'  => 'OpenPay',
				'fields' => array(
					'openpay_merchant_id' => array( 'label' => 'ID de comercio (Merchant ID)', 'type' => 'text' ),
					'openpay_public_key'  => array( 'label' => 'Llave pública (pk_…)', 'type' => 'text' ),
					'openpay_private_key' => array( 'label' => 'Llave privada (sk_…)', 'type' => 'password' ),
					'openpay_3ds'         => array( 'label' => '3D Secure', 'type' => 'select', 'options' => array( 'auto' => 'Según reglas de OpenPay', 'force' => 'Siempre', 'off' => 'Nunca' ), 'default' => 'auto' ),
					'openpay_webhook_user' => array( 'label' => 'Usuario del webhook', 'type' => 'text', 'help' => 'El mismo usuario y contraseña que configures en el webhook del panel de OpenPay.' ),
					'openpay_webhook_pass' => array( 'label' => 'Contraseña del webhook', 'type' => 'password' ),
				),
			),
			'paypal'  => array(
				'title'  => 'PayPal',
				'fields' => array(
					'paypal_client_id' => array( 'label' => 'Client ID', 'type' => 'text' ),
					'paypal_secret'    => array( 'label' => 'Secret', 'type' => 'password' ),
				),
			),
			'redpass' => array(
				'title'  => 'RedPass (códigos oficiales)',
				'fields' => array(
					'redpass_base_url' => array( 'label' => 'URL base de la API', 'type' => 'url', 'help' => 'La que entregue RedPass, p. ej. https://api.redpass.mx/v1' ),
					'redpass_api_key'  => array( 'label' => 'API key / token', 'type' => 'password' ),
					'redpass_client_id' => array( 'label' => 'ID de cliente (si aplica)', 'type' => 'text' ),
				),
			),
		);
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function get( $key, $default = '' ) {
		$const = 'TQC_' . strtoupper( $key );
		if ( defined( $const ) ) {
			return constant( $const );
		}
		$opts = get_option( self::OPTION, array() );
		if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
			return $opts[ $key ];
		}
		foreach ( self::fields() as $section ) {
			if ( isset( $section['fields'][ $key ]['default'] ) ) {
				return $section['fields'][ $key ]['default'];
			}
		}
		return $default;
	}

	public static function is_sandbox() {
		return 'live' !== self::get( 'mode' );
	}

	public static function menu() {
		add_options_page( 'TaquillApp', 'TaquillApp', 'manage_options', 'tqc-settings', array( __CLASS__, 'render' ) );
	}

	public static function register() {
		register_setting(
			'tqc_settings_group',
			self::OPTION,
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) )
		);
	}

	public static function sanitize( $input ) {
		$old = get_option( self::OPTION, array() );
		$out = array();
		foreach ( self::fields() as $section ) {
			foreach ( $section['fields'] as $key => $f ) {
				$val = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
				// Las contraseñas vacías conservan el valor anterior (no se muestran en el formulario).
				if ( 'password' === $f['type'] && '' === $val ) {
					$out[ $key ] = isset( $old[ $key ] ) ? $old[ $key ] : '';
					continue;
				}
				switch ( $f['type'] ) {
					case 'email':
						$out[ $key ] = sanitize_email( $val );
						break;
					case 'url':
						$out[ $key ] = esc_url_raw( untrailingslashit( $val ) );
						break;
					case 'number':
						$out[ $key ] = absint( $val );
						break;
					case 'select':
						$out[ $key ] = array_key_exists( $val, $f['options'] ) ? $val : $f['default'];
						break;
					default:
						$out[ $key ] = sanitize_text_field( $val );
				}
			}
		}
		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts         = get_option( self::OPTION, array() );
		$webhook_url  = rest_url( TQC_Rest::NS . '/webhooks/openpay' );
		$verification = get_option( 'tqc_openpay_verification_code' );
		?>
		<div class="wrap">
			<h1>TaquillApp</h1>
			<p>Las llaves también se pueden definir como constantes en <code>wp-config.php</code> (por ejemplo <code>TQC_OPENPAY_PRIVATE_KEY</code>). Si existe la constante, el campo aparece bloqueado.</p>

			<div class="notice notice-info inline"><p>
				<strong>Webhook de OpenPay:</strong> registra esta URL en el panel de OpenPay (Configuración → Webhooks) con los eventos de cargos:<br>
				<code><?php echo esc_html( $webhook_url ); ?></code>
				<?php if ( $verification ) : ?>
					<br>Código de verificación recibido: <code><?php echo esc_html( $verification ); ?></code>
				<?php endif; ?>
			</p></div>

			<form method="post" action="options.php">
				<?php settings_fields( 'tqc_settings_group' ); ?>
				<?php foreach ( self::fields() as $section ) : ?>
					<h2><?php echo esc_html( $section['title'] ); ?></h2>
					<table class="form-table" role="presentation">
						<?php foreach ( $section['fields'] as $key => $f ) :
							$const  = 'TQC_' . strtoupper( $key );
							$locked = defined( $const );
							$value  = isset( $opts[ $key ] ) ? $opts[ $key ] : ( isset( $f['default'] ) ? $f['default'] : '' );
							$name   = self::OPTION . '[' . $key . ']';
							?>
							<tr>
								<th scope="row"><label for="tqc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f['label'] ); ?></label></th>
								<td>
									<?php if ( $locked ) : ?>
										<code>Definido en wp-config.php (<?php echo esc_html( $const ); ?>)</code>
									<?php elseif ( 'select' === $f['type'] ) : ?>
										<select id="tqc-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>">
											<?php foreach ( $f['options'] as $k => $label ) : ?>
												<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $value, $k ); ?>><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									<?php elseif ( 'password' === $f['type'] ) : ?>
										<input id="tqc-<?php echo esc_attr( $key ); ?>" type="password" class="regular-text" name="<?php echo esc_attr( $name ); ?>" value="" autocomplete="new-password" placeholder="<?php echo ! empty( $opts[ $key ] ) ? '•••••••• (guardada; escribe para reemplazar)' : ''; ?>">
									<?php else : ?>
										<input id="tqc-<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $f['type'] ); ?>" class="regular-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
									<?php endif; ?>
									<?php if ( ! empty( $f['help'] ) ) : ?>
										<p class="description"><?php echo esc_html( $f['help'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
