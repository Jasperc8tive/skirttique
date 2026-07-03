<?php
/**
 * Title: Homepage
 * Slug: skirttique/homepage
 * Categories: skirttique
 * Block Types: core/template-part/homepage
 * Inserter: no
 *
 * The front page, rendered entirely through the canonical component
 * layer (inc/components.php) — the same renderers the Skirttique block
 * library uses, so this pattern and an editor-composed page produce
 * identical markup. House Settings values flow in as component args;
 * every value falls back to the shipped voice when blank.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

use Skirttique\Components;

$st_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

// Owner-edited copy (Skirttique → House Settings).
$st_house = (array) get_option( 'skirttique_house', array() );
$st_text  = static function ( string $key, string $default ) use ( $st_house ): string {
	$value = trim( (string) ( $st_house[ $key ] ?? '' ) );

	return '' !== $value ? $value : $default;
};

// Fallback editorial photography (Design System §Imagery) — used until
// the owner sets media (House Settings / category thumbnails).
$st_collection_fallbacks = array(
	'maxi'            => 'photo-1762342676026-09e25daaf607',
	'midi'            => 'photo-1722486245824-7bb0ff9827dc',
	'limited-edition' => 'photo-1688582949975-98a0750d7505',
	'bespoke'         => 'photo-1604648145659-29f52af5cf7f',
);
?>

<main id="main-content">

<?php
// Hero — the page's h1, eager + preloaded (inc/assets.php).
echo Components\hero( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'      => $st_text( 'hero_eyebrow', __( 'Collection I', 'skirttique' ) ),
		'statement'    => $st_text( 'hero_statement', __( 'The skirt, reconsidered', 'skirttique' ) ),
		'sub'          => $st_text( 'hero_sub', __( 'Midi and maxi silhouettes cut for movement, made to be kept.', 'skirttique' ) ),
		'cta_label'    => $st_text( 'hero_cta', __( 'Shop the collection', 'skirttique' ) ),
		'cta_url'      => $st_shop_url,
		'image_id'     => absint( $st_house['hero_image_id'] ?? 0 ),
		'fallback_img' => '<img src="https://images.unsplash.com/photo-1693259317871-6dfeb116c763?q=80&amp;w=1800&amp;auto=format&amp;fit=crop" alt="" loading="eager" fetchpriority="high" width="1800" height="1200">',
		'parallax'     => true,
	)
);

echo Components\collection_cards( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'         => __( 'The collections', 'skirttique' ),
		'title'           => __( 'Four ways to wear the house', 'skirttique' ),
		'id'              => 'st-collections-title',
		'fallback_images' => $st_collection_fallbacks,
	)
);

echo Components\editorial( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'      => __( 'The house view', 'skirttique' ),
		'statement'    => $st_text( 'philosophy_statement', __( 'A skirt is not an afterthought to an outfit. It is the architecture of one.', 'skirttique' ) ),
		'prose'        => $st_text( 'philosophy_prose', __( 'Skirttique exists for women who dress with intention — cut for the boardroom and the aisle, the flight and the function, in fabrics chosen to move the way you do. Every piece is made in limited runs, then retired.', 'skirttique' ) ),
		'cta_label'    => __( 'The house, in full', 'skirttique' ),
		'cta_url'      => home_url( '/about/' ),
		'image_id'     => absint( $st_house['philosophy_image_id'] ?? 0 ),
		'id'           => 'st-philosophy-title',
		'fallback_img' => '<img src="https://images.unsplash.com/photo-1774380255851-72e8a36d7a59?q=80&amp;w=1200&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="1200" height="1500">',
	)
);

echo Components\product_grid( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'    => __( 'New in', 'skirttique' ),
		'title'      => __( 'The current edit', 'skirttique' ),
		'id'         => 'st-edit-title',
		'products'   => Components\products( 'newest', 4 ),
		'more_label' => __( 'View everything', 'skirttique' ),
		'more_url'   => $st_shop_url,
	)
);

/**
 * Filter the homepage voice quotes (placeholder client-voice copy until
 * real press and reviews exist — deliberately no real publication names,
 * we never fabricate an endorsement). Each item: quote + source.
 *
 * @param list<array{quote: string, source: string}> $quotes Quotes, rotated in order.
 */
$st_quotes = apply_filters(
	'skirttique_press_quotes',
	array(
		array(
			'quote'  => __( 'The Halima maxi went from a Lagos boardroom to a London wedding in one suitcase.', 'skirttique' ),
			'source' => __( 'A client, Lagos', 'skirttique' ),
		),
		array(
			'quote'  => __( 'Finally — a house that treats the skirt as the main event, not the afterthought.', 'skirttique' ),
			'source' => __( 'A client, Dubai', 'skirttique' ),
		),
		array(
			'quote'  => __( 'Three seasons in and the pleats still fall exactly where they did on day one.', 'skirttique' ),
			'source' => __( 'A client, New York', 'skirttique' ),
		),
	)
);

echo Components\testimonials( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'  => __( 'In their words', 'skirttique' ),
		'quotes'   => $st_quotes,
		'interval' => 9000,
	)
);

echo Components\cta_band( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'statement' => $st_text( 'closing_statement', __( 'Worn in five countries. Cut in one house.', 'skirttique' ) ),
		'cta_label' => __( 'Join the house list', 'skirttique' ),
		'cta_url'   => '#st-house-list',
	)
);
?>

</main>
