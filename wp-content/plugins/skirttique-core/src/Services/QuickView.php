<?php
/**
 * Quick view service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * `wc-ajax=skirttique_quickview` — returns the quick-view fragment for
 * one product. The plugin owns the endpoint (validation, caching
 * headers); the presentation comes from whatever hooks the
 * `skirttique_quickview_html` filter (the theme).
 */
final class QuickView implements ServiceInterface {

	public function register(): void {
		add_action( 'wc_ajax_skirttique_quickview', array( $this, 'render' ) );
	}

	/**
	 * Render the quick-view HTML for ?id=.
	 */
	public function render(): void {
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only fragment.
		$product = $id ? wc_get_product( $id ) : false;

		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			wp_send_json( array( 'error' => true ), 404 );
		}

		/**
		 * Filter the quick-view markup for a product.
		 *
		 * @param string      $html    Fragment HTML (escaped by the renderer).
		 * @param \WC_Product $product The product.
		 */
		$html = apply_filters( 'skirttique_quickview_html', '', $product );

		wp_send_json( array( 'html' => $html ) );
	}
}
