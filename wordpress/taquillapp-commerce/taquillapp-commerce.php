<?php
/**
 * Plugin Name:       TaquillApp Commerce
 * Description:       Catálogo, pedidos, pagos con OpenPay y PayPal, y emisión de códigos oficiales de cine con RedPass.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            TaquillApp
 * Text Domain:       taquillapp
 */

defined( 'ABSPATH' ) || exit;

define( 'TQC_VERSION', '1.0.0' );
define( 'TQC_FILE', __FILE__ );
define( 'TQC_DIR', plugin_dir_path( __FILE__ ) );
define( 'TQC_URL', plugin_dir_url( __FILE__ ) );

require_once TQC_DIR . 'includes/class-tqc-settings.php';
require_once TQC_DIR . 'includes/class-tqc-products.php';
require_once TQC_DIR . 'includes/class-tqc-orders.php';
require_once TQC_DIR . 'includes/class-tqc-openpay.php';
require_once TQC_DIR . 'includes/class-tqc-paypal.php';
require_once TQC_DIR . 'includes/class-tqc-redpass.php';
require_once TQC_DIR . 'includes/class-tqc-fulfillment.php';
require_once TQC_DIR . 'includes/class-tqc-emails.php';
require_once TQC_DIR . 'includes/class-tqc-rest.php';
require_once TQC_DIR . 'includes/class-tqc-admin.php';

final class TQC_Plugin {

	public static function init() {
		TQC_Settings::init();
		TQC_Products::init();
		TQC_Orders::init();
		TQC_Fulfillment::init();
		TQC_Rest::init();
		TQC_Admin::init();

		// Configuración pública que el tema entrega al navegador (window.TQ_CONFIG).
		// Solo llaves PÚBLICAS: nunca agregar aquí llaves privadas o secretos.
		add_filter( 'taquillapp_frontend_config', array( __CLASS__, 'frontend_config' ) );
	}

	public static function frontend_config( $config ) {
		$sandbox = TQC_Settings::is_sandbox();

		return array_merge(
			(array) $config,
			array(
				'restUrl'  => esc_url_raw( rest_url( TQC_Rest::NS ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'currency' => 'MXN',
				'sandbox'  => $sandbox,
				'products' => TQC_Products::catalog(),
				'openpay'  => array(
					'merchantId' => TQC_Settings::get( 'openpay_merchant_id' ),
					'publicKey'  => TQC_Settings::get( 'openpay_public_key' ),
					'sandbox'    => $sandbox,
				),
				'paypal'   => array(
					'clientId' => TQC_Settings::get( 'paypal_client_id' ),
				),
			)
		);
	}

	public static function activate() {
		TQC_Products::register();
		TQC_Orders::register();
		TQC_Products::seed_defaults();
		TQC_Fulfillment::schedule();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		TQC_Fulfillment::unschedule();
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'TQC_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TQC_Plugin', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'TQC_Plugin', 'init' ) );
