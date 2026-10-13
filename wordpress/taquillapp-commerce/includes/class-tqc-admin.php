<?php
/**
 * Pantallas de administración de pedidos: columnas, detalle y acciones manuales.
 */

defined( 'ABSPATH' ) || exit;

class TQC_Admin {

	public static function init() {
		add_filter( 'manage_' . TQC_Orders::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . TQC_Orders::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'add_meta_boxes_' . TQC_Orders::CPT, array( __CLASS__, 'meta_boxes' ) );
		add_action( 'admin_post_tqc_order_action', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function columns( $cols ) {
		return array(
			'cb'        => $cols['cb'],
			'title'     => 'Pedido',
			'tq_status' => 'Estado',
			'tq_total'  => 'Total',
			'tq_gateway' => 'Pago',
			'date'      => $cols['date'],
		);
	}

	public static function column( $col, $id ) {
		switch ( $col ) {
			case 'tq_status':
				$s     = get_post_meta( $id, '_tq_status', true );
				$color = array( 'fulfilled' => '#1a7f37', 'fulfillment_failed' => '#b32d2e', 'failed' => '#b32d2e', 'awaiting_payment' => '#9a6700' );
				printf( '<strong style="color:%s">%s</strong>', esc_attr( $color[ $s ] ?? '#1d2327' ), esc_html( TQC_Orders::STATUSES[ $s ] ?? $s ) );
				if ( 'sandbox' === get_post_meta( $id, '_tq_mode', true ) ) {
					echo ' <span style="color:#787c82">(prueba)</span>';
				}
				break;
			case 'tq_total':
				echo esc_html( '$' . number_format( (float) get_post_meta( $id, '_tq_total', true ), 2 ) );
				break;
			case 'tq_gateway':
				echo esc_html( ucfirst( (string) get_post_meta( $id, '_tq_gateway', true ) ) );
				break;
		}
	}

	public static function meta_boxes() {
		remove_meta_box( 'submitdiv', TQC_Orders::CPT, 'side' );
		add_meta_box( 'tqc_order', 'Detalle del pedido', array( __CLASS__, 'render' ), TQC_Orders::CPT, 'normal', 'high' );
		add_meta_box( 'tqc_order_actions', 'Acciones', array( __CLASS__, 'render_actions' ), TQC_Orders::CPT, 'side', 'high' );
	}

	public static function render( $post ) {
		$o = TQC_Orders::get( $post->ID );
		$c = $o['customer'];
		?>
		<p>
			<strong>Estado:</strong> <?php echo esc_html( TQC_Orders::STATUSES[ $o['status'] ] ?? $o['status'] ); ?> ·
			<strong>Total:</strong> <?php echo esc_html( '$' . number_format( $o['total'], 2 ) ); ?> MXN ·
			<strong>Pago:</strong> <?php echo esc_html( $o['gateway'] ? ucfirst( $o['gateway'] ) . ' (' . $o['gateway_ref'] . ')' : '—' ); ?>
			<?php echo 'sandbox' === $o['mode'] ? ' · <em>Pedido de prueba</em>' : ''; ?>
		</p>
		<p><strong>Cliente:</strong> <?php echo esc_html( trim( ( $c['name'] ?? '' ) . ' ' . ( $c['last_name'] ?? '' ) ) ); ?> · <?php echo esc_html( $c['email'] ?? '' ); ?> <?php echo ! empty( $c['phone'] ) ? '· ' . esc_html( $c['phone'] ) : ''; ?></p>

		<table class="widefat striped">
			<thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>SKU RedPass</th><th>Códigos</th></tr></thead>
			<tbody>
			<?php foreach ( $o['items'] as $line ) : ?>
				<tr>
					<td><?php echo esc_html( $line['name'] . ' · ' . $line['chain'] ); ?></td>
					<td><?php echo (int) $line['qty']; ?></td>
					<td><?php echo esc_html( '$' . number_format( $line['unit_price'], 2 ) ); ?></td>
					<td><?php echo esc_html( $line['redpass_sku'] ? $line['redpass_sku'] : '—' ); ?></td>
					<td><code><?php echo esc_html( implode( ', ', (array) ( $line['codes'] ?? array() ) ) ); ?></code></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h4>Historial</h4>
		<ul style="max-height:240px;overflow:auto;margin:0">
			<?php foreach ( array_reverse( (array) get_post_meta( $post->ID, '_tq_log', true ) ) as $entry ) : ?>
				<li><code><?php echo esc_html( $entry['at'] ?? '' ); ?></code> <?php echo esc_html( $entry['msg'] ?? '' ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	public static function render_actions( $post ) {
		$o   = TQC_Orders::get( $post->ID );
		$url = function ( $action ) use ( $post ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=tqc_order_action&do=' . $action . '&order=' . $post->ID ), 'tqc_order_' . $post->ID );
		};
		if ( in_array( $o['status'], array( 'paid', 'fulfillment_failed' ), true ) ) {
			echo '<p><a class="button button-primary" href="' . esc_url( $url( 'fulfill' ) ) . '">Reintentar emisión de códigos</a></p>';
		}
		if ( 'fulfilled' === $o['status'] ) {
			echo '<p><a class="button" href="' . esc_url( $url( 'resend' ) ) . '">Reenviar códigos al cliente</a></p>';
		}
		if ( 'awaiting_payment' === $o['status'] && 'openpay' === $o['gateway'] ) {
			echo '<p><a class="button" href="' . esc_url( $url( 'sync' ) ) . '">Consultar pago en OpenPay</a></p>';
		}
		echo '<p class="description">Los reembolsos se hacen desde el panel de OpenPay o PayPal.</p>';
	}

	public static function handle_action() {
		$id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		check_admin_referer( 'tqc_order_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Sin permiso.' );
		}
		$do  = isset( $_GET['do'] ) ? sanitize_key( $_GET['do'] ) : '';
		$msg = 'done';
		if ( 'fulfill' === $do ) {
			delete_post_meta( $id, '_tq_fulfill_attempts' );
			$msg = TQC_Fulfillment::fulfill( $id ) ? 'fulfilled' : 'still_failing';
		} elseif ( 'resend' === $do ) {
			TQC_Emails::codes( $id );
			TQC_Orders::log( $id, 'Códigos reenviados por un administrador.' );
			$msg = 'resent';
		} elseif ( 'sync' === $do ) {
			TQC_Fulfillment::sync_openpay( $id );
		}
		wp_safe_redirect( add_query_arg( 'tqc_msg', $msg, admin_url( 'post.php?post=' . $id . '&action=edit' ) ) );
		exit;
	}

	public static function notices() {
		if ( empty( $_GET['tqc_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$msgs = array(
			'fulfilled'     => array( 'success', 'Códigos emitidos y enviados al cliente.' ),
			'still_failing' => array( 'error', 'No se pudieron emitir todos los códigos. Revisa el historial del pedido.' ),
			'resent'        => array( 'success', 'Códigos reenviados.' ),
			'done'          => array( 'info', 'Listo.' ),
		);
		$m = $msgs[ sanitize_key( $_GET['tqc_msg'] ) ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $m ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $m[0] ), esc_html( $m[1] ) );
		}
	}
}
