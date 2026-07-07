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
 * as-you-type results (search.ts), plus the matching widened onto the
 * full catalog search page so the two agree.
 *
 * `wc-ajax=skirttique_search&term=maxi` →
 * `{ cards: [html…], total: n, url: "/?s=maxi&post_type=product" }`
 *
 * WordPress's native search only reads post title/content. A shopper who
 * types a fabric ("silk"), a cut ("pleated"), a colour, a collection name
 * or a SKU expects the piece back even when the word is not in its title,
 * so matching_ids() unions the native relevance match with products whose
 * SKU or whose category / tag / attribute term names contain the term.
 * broaden_catalog_search() widens the main results query the same way, so
 * "view all" lands on the same set the drawer previewed.
 *
 * Card markup comes from the theme via the shared
 * `skirttique_product_card_html` filter.
 */
final class Search implements ServiceInterface {

	private const MAX_RESULTS = 4;

	/** Upper bound on ids gathered for the "N results" count and the drawer. */
	private const MAX_SCAN = 200;

	public function register(): void {
		add_action( 'wc_ajax_skirttique_search', array( $this, 'results' ) );

		// Widen the full catalog search page the same way the drawer widens,
		// by OR-ing the SKU / term matches into WordPress's search clause.
		add_filter( 'posts_search', array( $this, 'broaden_catalog_search' ), 10, 2 );
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

		$ids   = self::matching_ids( $term, self::MAX_SCAN );
		$cards = array();
		foreach ( $ids as $id ) {
			if ( count( $cards ) >= self::MAX_RESULTS ) {
				break;
			}
			$product = wc_get_product( $id );
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
				'total' => count( $ids ),
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

	/**
	 * Product ids matching a term across title/content (native relevance),
	 * SKU, and category / tag / attribute term names — in that precedence,
	 * deduped. Published only; catalog-visibility is left to the caller
	 * (the drawer skips hidden pieces per card; the main query applies
	 * WooCommerce's own visibility clause).
	 *
	 * @return list<int>
	 */
	public static function matching_ids( string $term, int $limit ): array {
		$term = trim( $term );
		if ( mb_strlen( $term ) < 2 ) {
			return array();
		}

		$title = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				's'              => $term,
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'relevance',
			)
		);

		$ids = array_merge( array_map( 'intval', $title->posts ), self::broad_ids( $term, $limit ) );

		return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
	}

	/**
	 * Products whose SKU or category / tag / attribute term names contain
	 * the term (the part WordPress's title/content search misses).
	 *
	 * @return list<int>
	 */
	private static function broad_ids( string $term, int $limit ): array {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $term ) . '%';
		$ids  = array();

		// SKU (partial).
		$sku = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_sku'
				 WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_value LIKE %s
				 LIMIT %d",
				$like,
				$limit
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', (array) $sku ) );

		// Products in matching taxonomy terms (collections, tags, attributes
		// such as colour and fabric).
		$taxonomies = array_merge(
			array( 'product_cat', 'product_tag' ),
			function_exists( 'wc_get_attribute_taxonomy_names' ) ? wc_get_attribute_taxonomy_names() : array()
		);
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomies,
				'name__like' => $term,
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $terms ) && $terms ) {
			$tt_ids = array_map( 'intval', wp_list_pluck( $terms, 'term_taxonomy_id' ) );
			$in     = implode( ',', $tt_ids ); // intval'd above — safe to inline.
			$objects = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
					 INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
					 WHERE tr.term_taxonomy_id IN ({$in}) AND p.post_type = 'product' AND p.post_status = 'publish'
					 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in is a list of intvals.
					$limit
				)
			);
			$ids = array_merge( $ids, array_map( 'intval', (array) $objects ) );
		}

		return $ids;
	}

	/**
	 * Widen the main catalog search query: OR the SKU / term matches into
	 * WordPress's title/content search clause. Leaves post__in, the size /
	 * price / on-sale facets, sort and pagination untouched — it only makes
	 * the search itself broader.
	 *
	 * @param string    $search The WHERE search clause.
	 * @param \WP_Query $query  The query.
	 */
	public function broaden_catalog_search( string $search, \WP_Query $query ): string {
		if ( '' === $search || is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return $search;
		}
		if ( 'product' !== $query->get( 'post_type' ) ) {
			return $search;
		}

		$term = trim( (string) $query->get( 's' ) );
		if ( mb_strlen( $term ) < 2 ) {
			return $search;
		}

		$ids = self::broad_ids( $term, self::MAX_SCAN );
		if ( ! $ids ) {
			return $search;
		}

		global $wpdb;
		$in    = implode( ',', array_map( 'intval', array_unique( $ids ) ) );
		$inner = preg_replace( '/^\s*AND\s*/', '', $search );

		return " AND ( {$inner} OR {$wpdb->posts}.ID IN ({$in}) )";
	}
}
