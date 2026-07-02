<?php
/**
 * Title: Checkout Header
 * Slug: skirttique/checkout-header
 * Categories: skirttique
 * Inserter: no
 *
 * Distraction-reduced checkout chrome: the way back to the bag, the
 * logotype home, and the reassurance that this step is secure. No nav,
 * no drawers, no search — nothing that competes with finishing.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_bag_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
?>

<header class="st-checkout-head">
	<a class="st-checkout-head__back" href="<?php echo esc_url( $st_bag_url ); ?>">
		<svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M10 2L4 8l6 6"/></svg>
		<?php esc_html_e( 'Back to bag', 'skirttique' ); ?>
	</a>
	<a class="st-checkout-head__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Skirttique', 'skirttique' ); ?></a>
	<p class="st-checkout-head__secure">
		<svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><rect x="3" y="7" width="10" height="7" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
		<?php esc_html_e( 'Secure checkout', 'skirttique' ); ?>
	</p>
</header>
