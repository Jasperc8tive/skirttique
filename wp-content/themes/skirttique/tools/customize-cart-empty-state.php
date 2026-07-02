<?php
/**
 * Replace the cart block's stock empty state ("Your cart is currently
 * empty!" + New in store grid) with the house voice. Idempotent — run
 * any time with:
 *
 *   npx wp-env run cli -- wp eval-file wp-content/themes/skirttique/tools/customize-cart-empty-state.php
 *
 * (wp eval-file forbids declare(strict_types) — leave it off.)
 *
 * @package Skirttique
 */

$st_markup = <<<'HTML'
<!-- wp:heading {"textAlign":"center","className":"wc-block-cart__empty-cart__title"} -->
<h2 class="wp-block-heading has-text-align-center wc-block-cart__empty-cart__title">Your bag is empty</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"st-bag__empty-note"} -->
<p class="has-text-align-center st-bag__empty-note">Nothing kept yet — the collection is waiting.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"st-bag__empty-link"} -->
<p class="has-text-align-center st-bag__empty-link"><a class="st-hemline" href="/shop/">View everything</a></p>
<!-- /wp:paragraph -->
HTML;

$st_page_id = wc_get_page_id( 'cart' );
$st_post    = get_post( $st_page_id );

if ( ! $st_post ) {
	echo "cart page missing\n";
	return;
}

$st_replacement = array_values(
	array_filter(
		parse_blocks( $st_markup ),
		static function ( $block ) {
			return null !== $block['blockName'];
		}
	)
);

$st_swap = function ( array $blocks ) use ( &$st_swap, $st_replacement ) {
	foreach ( $blocks as &$block ) {
		if ( 'woocommerce/empty-cart-block' === $block['blockName'] ) {
			$first = $block['innerContent'][0];
			$last  = end( $block['innerContent'] );

			$block['innerBlocks']  = $st_replacement;
			$block['innerContent'] = array_merge(
				array( $first ),
				array_fill( 0, count( $st_replacement ), null ),
				array( $last )
			);
		} elseif ( $block['innerBlocks'] ) {
			$block['innerBlocks'] = $st_swap( $block['innerBlocks'] );
		}
	}

	return $blocks;
};

$st_blocks = $st_swap( parse_blocks( $st_post->post_content ) );

wp_update_post(
	array(
		'ID'           => $st_page_id,
		'post_content' => serialize_blocks( $st_blocks ),
	)
);

echo "cart empty state replaced\n";
