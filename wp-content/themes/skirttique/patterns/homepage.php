<?php
/**
 * Title: Homepage
 * Slug: skirttique/homepage
 * Categories: skirttique
 * Block Types: core/template-part/homepage
 * Inserter: no
 *
 * Homepage v2 (Stage 19): all thirteen sections, rendered through the
 * canonical component layer (inc/components.php) — the same renderers
 * the Skirttique block library uses, so this composition and an
 * editor-built page produce identical markup. House Settings values
 * flow in as component args; every value falls back to the shipped
 * voice when blank.
 *
 * Homepage Builder: if the owner composes their own front page from
 * Skirttique blocks (edit the front page in the block editor), that
 * composition replaces this one entirely. A blank front page renders
 * the shipped homepage below.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

use Skirttique\Components;

// Owner-composed homepage takes over (Homepage Builder).
$st_front_id = (int) get_option( 'page_on_front' );
$st_custom   = $st_front_id ? (string) get_post_field( 'post_content', $st_front_id ) : '';
if ( str_contains( $st_custom, '<!-- wp:skirttique/' ) ) {
	echo '<main id="main-content">' . do_blocks( $st_custom ) . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block-rendered content, escaped within each block.
	return;
}

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
/* 1 · Hero — the page's h1, eager + preloaded (inc/assets.php). */
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

/* 2 · Signature collections. */
echo Components\collection_cards( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'         => __( 'The collections', 'skirttique' ),
		'title'           => __( 'Four ways to wear the house', 'skirttique' ),
		'id'              => 'st-collections-title',
		'fallback_images' => $st_collection_fallbacks,
	)
);

/* 3 · New in — the current edit. */
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

/* 4 · Craftsmanship story. */
echo Components\editorial( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'      => __( 'The making', 'skirttique' ),
		'statement'    => $st_text( 'craft_statement', __( 'Cut slowly, finished by hand', 'skirttique' ) ),
		'prose'        => $st_text( 'craft_prose', __( 'Every piece passes through one atelier — pattern, cloth, pleat and hem — under hands that have cut skirts for decades. The result hangs the same in year three as it did on day one.', 'skirttique' ) ),
		'cta_label'    => __( 'Inside the atelier', 'skirttique' ),
		'cta_url'      => home_url( '/about/' ),
		'image_id'     => absint( $st_house['craft_image_id'] ?? 0 ),
		'media_side'   => 'left',
		'id'           => 'st-craft-title',
		'fallback_img' => '<img src="https://images.unsplash.com/photo-1688582949975-98a0750d7505?q=80&amp;w=1200&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="1200" height="1500">',
	)
);

/* 5 · Best sellers. */
echo Components\product_slider( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'  => __( 'Best sellers', 'skirttique' ),
		'title'    => __( 'The pieces they keep', 'skirttique' ),
		'id'       => 'st-sellers-title',
		'products' => Components\products( 'bestsellers', 8 ),
	)
);

/* 6 · Featured collection — editorial term-meta from Stage 18. */
echo Components\featured_collection( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'        => __( 'Collection in focus', 'skirttique' ),
		'slug'           => $st_text( 'featured_collection', '' ),
		'id'             => 'st-featured-title',
		'fallback_story' => __( 'Signature silhouettes in a numbered run — worn hard, kept long, retired for good.', 'skirttique' ),
		'fallback_img'   => '<img src="https://images.unsplash.com/photo-1722486245824-7bb0ff9827dc?q=80&amp;w=1600&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="1600" height="900">',
	)
);

/* 7 · Why Skirttique. */
echo Components\feature_list( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow' => __( 'Why Skirttique', 'skirttique' ),
		'title'   => __( 'The house difference', 'skirttique' ),
		'id'      => 'st-why-title',
		'items'   => Components\parse_lines(
			$st_text(
				'why_items',
				__( 'Limited runs|Each silhouette is cut in a numbered run, then retired.', 'skirttique' ) . "\n"
				. __( 'Fabric first|Cloth chosen for how it moves, not how it photographs.', 'skirttique' ) . "\n"
				. __( 'One atelier|Cut and finished in a single house in Lagos.', 'skirttique' ) . "\n"
				. __( 'Made to be kept|Built for years of wear, with care notes for every cloth.', 'skirttique' )
			),
			array( 'title', 'text' )
		),
	)
);

/* 8 · Featured product. */
echo Components\featured_product( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow'    => __( 'The featured piece', 'skirttique' ),
		'product_id' => absint( $st_house['featured_product_id'] ?? 0 ),
		'id'         => 'st-spotlight-title',
	)
);

/* 9 · Philosophy — the house view. */
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

/* 10 · Editorial lookbook — renders once a lookbook is published. */
echo Components\lookbook_feature( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'id'           => 'st-lookbook-title',
		'fallback_img' => '<img src="https://images.unsplash.com/photo-1762342676026-09e25daaf607?q=80&amp;w=1600&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="1600" height="900">',
	)
);

/**
 * 11 · In their words.
 *
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

/* 12 · Instagram — placeholder tiles until Stage 26 hydrates the feed. */
echo Components\instagram( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'id'              => 'st-instagram-title',
		'fallback_images' => array(
			'photo-1693259317871-6dfeb116c763',
			'photo-1762342676026-09e25daaf607',
			'photo-1722486245824-7bb0ff9827dc',
			'photo-1688582949975-98a0750d7505',
			'photo-1604648145659-29f52af5cf7f',
			'photo-1774380255851-72e8a36d7a59',
		),
	)
);

/* 13 · Closing band. */
echo Components\cta_band( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'statement' => $st_text( 'closing_statement', __( 'Worn in five countries. Cut in one house.', 'skirttique' ) ),
		'cta_label' => __( 'Join the house list', 'skirttique' ),
		'cta_url'   => '#st-house-list',
	)
);
?>

</main>
