<?php
/**
 * Cart AJAX service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * `wc-ajax=skirttique_add_to_cart` — like WooCommerce's own add_to_cart
 * endpoint but with variation support, so the PDP and quick view can add
 * a chosen size without a full page POST. Follows Woo's convention for
 * cart endpoints (no nonce — the cart is session-scoped and non-privileged,
 * matching WC_AJAX::add_to_cart).
 */
final class CartAjax implements ServiceInterface {

	public function register(): void {
		add_action( 'wc_ajax_skirttique_add_to_cart', array( $this, 'add_to_cart' ) );
	}

	/**
	 * Add a simple product or a specific variation to the cart, then
	 * return WooCommerce's refreshed fragments (mini-cart + our header
	 * count bubble ride along via the fragments filter).
	 */
	public function add_to_cart(): void {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			wp_send_json( array( 'error' => true ), 400 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- cart endpoints follow WC_AJAX::add_to_cart (session-scoped, no nonce).
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;

		$variation = array();
		if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
			foreach ( wp_unslash( $_POST['attributes'] ) as $key => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per pair below.
				$key = sanitize_title( (string) $key );
				if ( str_starts_with( $key, 'attribute_' ) ) {
					$variation[ $key ] = sanitize_text_field( (string) $value );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$product = wc_get_product( $product_id );

		if ( ! $product || 'publish' !== $product->get_status() ) {
			wp_send_json( array( 'error' => true ), 404 );
		}

		$passed = apply_filters(
			'woocommerce_add_to_cart_validation',
			true,
			$product_id,
			$quantity,
			$variation_id,
			$variation
		);

		$added = $passed
			? WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation )
			: false;

		if ( false === $added ) {
			// Mirror Woo's contract: hand the client the product URL so it
			// can fall back to the PDP, where notices explain the refusal.
			wp_send_json(
				array(
					'error'       => true,
					'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', $product->get_permalink(), $product_id ),
				)
			);
		}

		// Success notices would otherwise queue up and dump on the next
		// full page view — the drawer opening IS the confirmation.
		wc_clear_notices();

		\WC_AJAX::get_refreshed_fragments();
	}
}
