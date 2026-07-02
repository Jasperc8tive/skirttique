<?php
/**
 * My Account dashboard (theme override of WooCommerce's
 * myaccount/dashboard.php) — the signed-in overview: a line of house
 * voice and the three places an account actually goes.
 *
 * @package Skirttique
 * @version 4.4.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$st_tiles = array(
	array(
		'url'   => wc_get_account_endpoint_url( 'orders' ),
		'title' => __( 'Orders', 'skirttique' ),
		'note'  => __( 'Every piece, from placed to delivered.', 'skirttique' ),
	),
	array(
		'url'   => home_url( '/saved/' ),
		'title' => __( 'Saved pieces', 'skirttique' ),
		'note'  => __( 'The ones you are still deciding on.', 'skirttique' ),
	),
	array(
		'url'   => wc_get_account_endpoint_url( 'edit-address' ),
		'title' => __( 'Addresses', 'skirttique' ),
		'note'  => __( 'Where your pieces should arrive.', 'skirttique' ),
	),
	array(
		'url'   => wc_get_account_endpoint_url( 'edit-account' ),
		'title' => __( 'Account details', 'skirttique' ),
		'note'  => __( 'Your name, email, and password.', 'skirttique' ),
	),
);
?>

<p class="st-account__intro">
	<?php esc_html_e( 'Everything the house keeps for you, in one place. Your saved pieces follow you to any device you sign in on.', 'skirttique' ); ?>
</p>

<div class="st-account__tiles">
	<?php foreach ( $st_tiles as $st_tile ) : ?>
		<a class="st-account__tile" href="<?php echo esc_url( $st_tile['url'] ); ?>">
			<span class="st-account__tile-title"><?php echo esc_html( $st_tile['title'] ); ?></span>
			<span class="st-account__tile-note"><?php echo esc_html( $st_tile['note'] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>

<?php
/**
 * Woo's standard dashboard hook — kept so account extensions still land.
 */
do_action( 'woocommerce_account_dashboard' );
