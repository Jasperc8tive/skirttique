<?php
/**
 * Instant product search endpoint.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 22: the read-only fragment behind the header search drawer's
 * as-you-type results (search.ts).
 *
 * `wc-ajax=skirttique_search&term=maxi` →
 * `{ cards: [html…], total: n, url: "/?s=maxi&post_type=product" }`
 *
 * Card markup comes from the theme via the same
 * `skirttique_product_card_html` filter the other fragment endpoints
 * use — the drawer, the grid, and the rails all show one card. The
 * full-results URL renders through the theme's product-search template,
 * so "view all" lands on the same design.
 */
final class Search implements ServiceInterface {

	private const MAX_RESULTS = 4;

	public function register(): void {
		add_action( 'wc_ajax_skirttique_search', array( $this, 'results' ) );
	}

	/**
	 * Top product matches for ?term=.
	 */
	public function results(): void {
		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only fragment.

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json(
				array(
					'cards' => array(),
					'total' => 0,
					'url'   => '',
				)
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				's'              => $term,
				'posts_per_page' => self::MAX_RESULTS,
				'no_found_rows'  => false,
			)
		);

		$cards = array();
		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}

			/** This filter is documented in Services\RecentlyViewed. */
			$html = apply_filters( 'skirttique_product_card_html', '', $product );
			if ( '' !== $html ) {
				$cards[] = $html;
			}
		}

		wp_send_json(
			array(
				'cards' => $cards,
				'total' => (int) $query->found_posts,
				'url'   => add_query_arg(
					array(
						's'         => rawurlencode( $term ),
						'post_type' => 'product',
					),
					home_url( '/' )
				),
			)
		);
	}
}
