<?php
/**
 * WooCommerce presentation glue.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\WooCommerce;

/**
 * Current cart item count (0 when WooCommerce or the cart is unavailable).
 */
function cart_count(): int {
	if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Render the header cart-count bubble. Registered as a cart fragment so
 * WooCommerce refreshes it after every AJAX cart change.
 */
function cart_count_bubble(): string {
	$count = cart_count();

	return sprintf(
		'<span class="st-cart-count%s" data-st-cart-count aria-hidden="true">%s</span>',
		0 === $count ? ' is-empty' : '',
		esc_html( (string) $count )
	);
}

/**
 * Register header fragments with WooCommerce's cart-fragments refresh.
 *
 * @param array<string, string> $fragments Fragment selector → markup map.
 * @return array<string, string>
 */
function cart_fragments( array $fragments ): array {
	$fragments['[data-st-cart-count]'] = cart_count_bubble();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\cart_fragments' );

/**
 * Ensure the fragments script runs so the count and drawer stay live.
 */
function enqueue_fragments(): void {
	if ( function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_fragments', 20 );
