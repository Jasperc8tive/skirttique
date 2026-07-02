<?php
/**
 * Recently viewed / card fragments service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Recently-viewed tracking lives client-side (localStorage ring buffer —
 * no PII server-side, nothing to consent-gate). This service provides
 * the shared read-only endpoint both features render through:
 *
 * `wc-ajax=skirttique_product_cards&ids=1,2,3` → product-card HTML for
 * up to 12 ids, in the order given. Used by the recently-viewed rail
 * and the Saved (wishlist) page. Markup comes from the theme via the
 * `skirttique_product_card_html` filter.
 */
final class RecentlyViewed implements ServiceInterface {

	private const MAX_CARDS = 12;

	public function register(): void {
		add_action( 'wc_ajax_skirttique_product_cards', array( $this, 'cards' ) );
	}

	/**
	 * Render product cards for ?ids= (comma-separated).
	 */
	public function cards(): void {
		$raw = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only fragment.
		$ids = array_slice( array_filter( array_map( 'absint', explode( ',', $raw ) ) ), 0, self::MAX_CARDS );

		$cards = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
				continue;
			}

			/**
			 * Filter the card markup for a product (the theme's card
			 * component hooks this).
			 *
			 * @param string      $html    Card HTML.
			 * @param \WC_Product $product The product.
			 */
			$html = apply_filters( 'skirttique_product_card_html', '', $product );

			if ( '' !== $html ) {
				$cards[ $id ] = $html;
			}
		}

		wp_send_json( array( 'cards' => $cards ) );
	}
}
