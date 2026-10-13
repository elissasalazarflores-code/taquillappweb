<?php
/**
 * Tema TaquillApp.
 *
 * Los archivos header.php, front-page.php, footer.php y assets/ se generan desde el
 * sitio estático con tools/build-wp-theme.py. Edita el sitio estático y vuelve a
 * generar; los demás archivos del tema se editan aquí directamente.
 */

defined( 'ABSPATH' ) || exit;

define( 'TQ_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', array( 'script', 'style', 'search-form', 'gallery', 'caption' ) );
	}
);

/** URL de un archivo dentro de assets/ del tema. */
function tq_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

/** Ancla de la portada: "#catalogo" en la portada, "https://sitio/#catalogo" en otras páginas. */
function tq_anchor( $id ) {
	return is_front_page() ? '#' . $id : home_url( '/#' . $id );
}

/** Enlace a una página por su slug (por ejemplo las legales); "#" si aún no existe. */
function tq_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : '#';
}

function tq_contact_email() {
	return get_theme_mod( 'tq_contact_email', 'hola@taquillapp.com.mx' );
}

/* ---------------- Personalizador ---------------- */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section( 'tq_contact', array( 'title' => 'TaquillApp · Contacto' ) );
		$wp_customize->add_setting( 'tq_contact_email', array( 'default' => 'hola@taquillapp.com.mx', 'sanitize_callback' => 'sanitize_email' ) );
		$wp_customize->add_control( 'tq_contact_email', array( 'label' => 'Correo de contacto', 'section' => 'tq_contact', 'type' => 'email' ) );
	}
);

/* ---------------- Estilos y scripts ---------------- */
add_action(
	'wp_enqueue_scripts',
	function () {
		$v = TQ_THEME_VERSION;
		wp_enqueue_style( 'tq-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );
		wp_enqueue_style( 'tq-styles', tq_asset( 'css/styles.css' ), array( 'tq-fonts' ), $v );

		wp_enqueue_script( 'tq-data', tq_asset( 'js/data.js' ), array(), $v, true );
		wp_enqueue_script( 'tq-payments', tq_asset( 'js/payments.js' ), array( 'tq-data' ), $v, true );
		wp_enqueue_script( 'tq-app', tq_asset( 'js/app.js' ), array( 'tq-payments' ), $v, true );

		wp_add_inline_script( 'tq-data', 'window.TQ_THEME = ' . wp_json_encode( array( 'assets' => tq_asset( 'img/' ) ) ) . ';', 'before' );

		// El plugin "TaquillApp Commerce" llena esta configuración (catálogo, API y llaves públicas).
		// Sin el plugin queda vacía y el checkout funciona en modo demostración.
		$config = apply_filters( 'taquillapp_frontend_config', array() );
		if ( $config ) {
			wp_add_inline_script( 'tq-data', 'window.TQ_CONFIG = ' . wp_json_encode( $config ) . ';', 'before' );
		}
	}
);

add_action(
	'wp_head',
	function () {
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<meta name="theme-color" content="#0D0F1C">' . "\n";
		if ( ! has_site_icon() ) {
			echo '<link rel="icon" type="image/png" href="' . esc_url( tq_asset( 'img/favicon.png' ) ) . '">' . "\n";
		}
		if ( is_front_page() ) {
			echo '<meta name="description" content="' . esc_attr( 'Compra boletos, combos y paquetes de Cinépolis y Cinemex con más de 50% de descuento en las mejores salas. Recibe tu código al instante y vive mejor tu experiencia en el cine.' ) . '">' . "\n";
		}
	},
	1
);

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( is_front_page() ) {
			$parts = array( 'title' => 'TaquillApp · Más de 50% de descuento en cine' );
		}
		return $parts;
	}
);
