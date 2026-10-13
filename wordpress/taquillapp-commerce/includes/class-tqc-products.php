<?php
/**
 * Productos del catálogo (tipo de contenido "tq_product").
 * Se editan en WordPress → Productos TaquillApp. El orden se controla con "Orden" (menu_order).
 */

defined( 'ABSPATH' ) || exit;

class TQC_Products {

	const CPT = 'tq_product';

	const CHAINS = array( 'cinepolis' => 'Cinépolis', 'cinemex' => 'Cinemex' );
	const TYPES  = array( 'boleto' => 'Boleto', 'combo' => 'Combo', 'paquete' => 'Paquete' );
	const ICONS  = array( 'ticket' => 'Boleto', 'glasses' => 'Lentes 3D', 'star' => 'Estrella', 'crown' => 'Corona (VIP/Platino)', 'spark' => 'Destello (IMAX/4DX)', 'popcorn' => 'Palomitas', 'hotdog' => 'Hot dog', 'nachos' => 'Nachos', 'duo' => 'Doble boleto' );

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'       => array(
					'name'          => 'Productos TaquillApp',
					'singular_name' => 'Producto',
					'add_new_item'  => 'Agregar producto',
					'edit_item'     => 'Editar producto',
					'menu_name'     => 'Productos',
				),
				'public'       => false,
				'show_ui'      => true,
				'menu_icon'    => 'dashicons-tickets-alt',
				'supports'     => array( 'title', 'page-attributes' ),
				'show_in_rest' => false,
			)
		);
	}

	/* ---------------- Datos ---------------- */

	public static function catalog() {
		$posts = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'to_array' ), $posts ) ) );
	}

	public static function to_array( $post ) {
		$m = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, '_tq_' . $k, true );
		};
		$terms = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $m( 'terms' ) ) ) ) );
		return array(
			'id'    => $m( 'sku' ) ? $m( 'sku' ) : 'p' . $post->ID,
			'chain' => $m( 'chain' ),
			'type'  => $m( 'type' ),
			'icon'  => $m( 'icon' ) ? $m( 'icon' ) : 'ticket',
			'name'  => get_the_title( $post ),
			'days'  => $m( 'days' ),
			'price' => (float) $m( 'price' ),
			'tag'   => $m( 'tag' ),
			'desc'  => $m( 'desc' ),
			'terms' => $terms,
		);
	}

	/** Producto por SKU público (el "id" que usa el navegador). Incluye datos internos para cobrar y emitir. */
	public static function find( $sku ) {
		$posts = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_tq_sku',
				'meta_value'     => sanitize_key( $sku ),
			)
		);
		if ( ! $posts ) {
			return null;
		}
		$p                = self::to_array( $posts[0] );
		$p['post_id']     = $posts[0]->ID;
		$p['redpass_sku'] = get_post_meta( $posts[0]->ID, '_tq_redpass_sku', true );
		return $p;
	}

	public static function seed_defaults() {
		$existing = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
		if ( $existing ) {
			return;
		}
		$defaults = include TQC_DIR . 'includes/default-products.php';
		foreach ( $defaults as $i => $p ) {
			$id = wp_insert_post(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => $p['name'],
					'menu_order'  => $i,
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_tq_sku', $p['id'] );
			foreach ( array( 'chain', 'type', 'icon', 'days', 'price', 'tag', 'desc' ) as $k ) {
				update_post_meta( $id, '_tq_' . $k, isset( $p[ $k ] ) ? $p[ $k ] : '' );
			}
			update_post_meta( $id, '_tq_terms', implode( "\n", $p['terms'] ) );
			update_post_meta( $id, '_tq_redpass_sku', '' );
		}
	}

	/* ---------------- Admin ---------------- */

	public static function meta_boxes() {
		add_meta_box( 'tq_product_data', 'Datos del producto', array( __CLASS__, 'render_box' ), self::CPT, 'normal', 'high' );
	}

	public static function render_box( $post ) {
		wp_nonce_field( 'tq_product_save', 'tq_product_nonce' );
		$v = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, '_tq_' . $k, true );
		};
		$select = function ( $name, $options, $current ) {
			echo '<select name="tq[' . esc_attr( $name ) . ']">';
			foreach ( $options as $k => $label ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		};
		?>
		<table class="form-table" role="presentation">
			<tr><th>Identificador (SKU)</th><td><input name="tq[sku]" class="regular-text" value="<?php echo esc_attr( $v( 'sku' ) ); ?>" placeholder="cmx-trad-ld"><p class="description">Único, en minúsculas y sin espacios. Se usa en el carrito.</p></td></tr>
			<tr><th>Cadena</th><td><?php $select( 'chain', self::CHAINS, $v( 'chain' ) ); ?></td></tr>
			<tr><th>Tipo</th><td><?php $select( 'type', self::TYPES, $v( 'type' ) ); ?></td></tr>
			<tr><th>Ícono</th><td><?php $select( 'icon', self::ICONS, $v( 'icon' ) ); ?></td></tr>
			<tr><th>Precio (MXN)</th><td><input name="tq[price]" type="number" min="1" step="0.01" value="<?php echo esc_attr( $v( 'price' ) ); ?>"></td></tr>
			<tr><th>Días / validez corta</th><td><input name="tq[days]" class="regular-text" value="<?php echo esc_attr( $v( 'days' ) ); ?>" placeholder="Lunes a domingo"></td></tr>
			<tr><th>Etiqueta</th><td><input name="tq[tag]" class="regular-text" value="<?php echo esc_attr( $v( 'tag' ) ); ?>" placeholder="Top ventas (opcional)"></td></tr>
			<tr><th>Descripción</th><td><textarea name="tq[desc]" rows="2" class="large-text"><?php echo esc_textarea( $v( 'desc' ) ); ?></textarea></td></tr>
			<tr><th>Condiciones</th><td><textarea name="tq[terms]" rows="5" class="large-text"><?php echo esc_textarea( $v( 'terms' ) ); ?></textarea><p class="description">Una condición por línea.</p></td></tr>
			<tr><th>SKU en RedPass</th><td><input name="tq[redpass_sku]" class="regular-text" value="<?php echo esc_attr( $v( 'redpass_sku' ) ); ?>"><p class="description">Identificador del producto en RedPass. Sin este dato no se pueden emitir códigos en producción.</p></td></tr>
		</table>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['tq_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tq_product_nonce'] ) ), 'tq_product_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['tq'] ) ? (array) wp_unslash( $_POST['tq'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizado campo por campo.

		$sku = sanitize_key( isset( $in['sku'] ) ? $in['sku'] : '' );
		if ( '' === $sku ) {
			$sku = sanitize_key( sanitize_title( $post->post_title ) . '-' . $post_id );
		}
		update_post_meta( $post_id, '_tq_sku', $sku );
		update_post_meta( $post_id, '_tq_chain', array_key_exists( $in['chain'] ?? '', self::CHAINS ) ? $in['chain'] : 'cinepolis' );
		update_post_meta( $post_id, '_tq_type', array_key_exists( $in['type'] ?? '', self::TYPES ) ? $in['type'] : 'boleto' );
		update_post_meta( $post_id, '_tq_icon', array_key_exists( $in['icon'] ?? '', self::ICONS ) ? $in['icon'] : 'ticket' );
		update_post_meta( $post_id, '_tq_price', max( 0, round( (float) ( $in['price'] ?? 0 ), 2 ) ) );
		foreach ( array( 'days', 'tag', 'redpass_sku' ) as $k ) {
			update_post_meta( $post_id, '_tq_' . $k, sanitize_text_field( $in[ $k ] ?? '' ) );
		}
		update_post_meta( $post_id, '_tq_desc', sanitize_textarea_field( $in['desc'] ?? '' ) );
		update_post_meta( $post_id, '_tq_terms', sanitize_textarea_field( $in['terms'] ?? '' ) );
	}

	public static function columns( $cols ) {
		return array(
			'cb'      => $cols['cb'],
			'title'   => 'Producto',
			'tq_chain' => 'Cadena',
			'tq_type' => 'Tipo',
			'tq_price' => 'Precio',
			'tq_redpass' => 'SKU RedPass',
			'date'    => $cols['date'],
		);
	}

	public static function column( $col, $post_id ) {
		switch ( $col ) {
			case 'tq_chain':
				echo esc_html( self::CHAINS[ get_post_meta( $post_id, '_tq_chain', true ) ] ?? '—' );
				break;
			case 'tq_type':
				echo esc_html( self::TYPES[ get_post_meta( $post_id, '_tq_type', true ) ] ?? '—' );
				break;
			case 'tq_price':
				echo esc_html( '$' . number_format( (float) get_post_meta( $post_id, '_tq_price', true ), 2 ) );
				break;
			case 'tq_redpass':
				$sku = get_post_meta( $post_id, '_tq_redpass_sku', true );
				echo $sku ? esc_html( $sku ) : '<span style="color:#b32d2e">Falta</span>';
				break;
		}
	}
}
