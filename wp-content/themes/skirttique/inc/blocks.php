<?php
/**
 * The Skirttique block library.
 *
 * Twenty dynamic blocks (sixteen from Stage 17, four from Stage 19),
 * registered natively from one PHP manifest. Each render callback is a
 * thin adapter onto the canonical
 * component renderers in inc/components.php — the same functions the
 * shipped patterns call — so editor-composed pages and the patterns
 * produce identical, token-driven markup.
 *
 * The editor side (src/editor/index.jsx) mirrors these attribute
 * definitions in its FIELDS registry and previews through
 * ServerSideRender. Keep the two registries in sync when adding blocks.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\Blocks;

use Skirttique\Components;

/**
 * Block manifest: name → [attributes, render].
 *
 * @return array<string, array{attributes: array<string, array<string, mixed>>, render: callable}>
 */
function manifest(): array {
	$str  = static fn ( string $default = '' ): array => array( 'type' => 'string', 'default' => $default );
	$int  = static fn ( int $default = 0 ): array => array( 'type' => 'number', 'default' => $default );
	$bool = static fn ( bool $default = false ): array => array( 'type' => 'boolean', 'default' => $default );

	return array(

		'skirttique/hero'             => array(
			'attributes' => array(
				'eyebrow'   => $str(),
				'statement' => $str( __( 'The skirt, reconsidered', 'skirttique' ) ),
				'sub'       => $str(),
				'ctaLabel'  => $str(),
				'ctaUrl'    => $str(),
				'imageId'   => $int(),
				'isBanner'  => $bool(), // true = h2 (section banner), false = h1 (page hero).
				'parallax'  => $bool( true ),
			),
			'render'     => static fn ( array $a ): string => Components\hero(
				array(
					'eyebrow'       => $a['eyebrow'],
					'statement'     => $a['statement'],
					'sub'           => $a['sub'],
					'cta_label'     => $a['ctaLabel'],
					'cta_url'       => $a['ctaUrl'],
					'image_id'      => (int) $a['imageId'],
					'heading_level' => $a['isBanner'] ? 2 : 1,
					'parallax'      => (bool) $a['parallax'],
				)
			),
		),

		'skirttique/editorial'        => array(
			'attributes' => array(
				'eyebrow'   => $str(),
				'statement' => $str(),
				'prose'     => $str(),
				'ctaLabel'  => $str(),
				'ctaUrl'    => $str(),
				'imageId'   => $int(),
				'mediaSide' => $str( 'right' ),
			),
			'render'     => static fn ( array $a ): string => Components\editorial(
				array(
					'eyebrow'    => $a['eyebrow'],
					'statement'  => $a['statement'],
					'prose'      => $a['prose'],
					'cta_label'  => $a['ctaLabel'],
					'cta_url'    => $a['ctaUrl'],
					'image_id'   => (int) $a['imageId'],
					'media_side' => $a['mediaSide'],
				)
			),
		),

		'skirttique/cta'              => array(
			'attributes' => array(
				'statement' => $str(),
				'ctaLabel'  => $str(),
				'ctaUrl'    => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\cta_band(
				array(
					'statement' => $a['statement'],
					'cta_label' => $a['ctaLabel'],
					'cta_url'   => $a['ctaUrl'],
				)
			),
		),

		'skirttique/collection-cards' => array(
			'attributes' => array(
				'eyebrow' => $str( __( 'The collections', 'skirttique' ) ),
				'title'   => $str( __( 'Four ways to wear the house', 'skirttique' ) ),
			),
			'render'     => static fn ( array $a ): string => Components\collection_cards(
				array(
					'eyebrow' => $a['eyebrow'],
					'title'   => $a['title'],
				)
			),
		),

		'skirttique/product-grid'     => array(
			'attributes' => array(
				'eyebrow'   => $str(),
				'title'     => $str(),
				'source'    => $str( 'newest' ), // newest|bestsellers|sale|category|handpicked.
				'extra'     => $str(),           // category slug or comma-separated ids.
				'count'     => $int( 4 ),
				'moreLabel' => $str(),
				'moreUrl'   => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\product_grid(
				array(
					'eyebrow'    => $a['eyebrow'],
					'title'      => $a['title'],
					'products'   => Components\products( $a['source'], (int) $a['count'], $a['extra'] ),
					'more_label' => $a['moreLabel'],
					'more_url'   => $a['moreUrl'],
				)
			),
		),

		'skirttique/product-slider'   => array(
			'attributes' => array(
				'eyebrow' => $str(),
				'title'   => $str(),
				'source'  => $str( 'newest' ),
				'extra'   => $str(),
				'count'   => $int( 8 ),
			),
			'render'     => static fn ( array $a ): string => Components\product_slider(
				array(
					'eyebrow'  => $a['eyebrow'],
					'title'    => $a['title'],
					'products' => Components\products( $a['source'], (int) $a['count'], $a['extra'] ),
				)
			),
		),

		'skirttique/testimonials'     => array(
			'attributes' => array(
				'eyebrow'  => $str( __( 'In their words', 'skirttique' ) ),
				'quotes'   => $str(), // One per line: Quote|Source.
				'interval' => $int( 9000 ),
			),
			'render'     => static fn ( array $a ): string => Components\testimonials(
				array(
					'eyebrow'  => $a['eyebrow'],
					'quotes'   => Components\parse_lines( $a['quotes'], array( 'quote', 'source' ) ),
					'interval' => (int) $a['interval'],
				)
			),
		),

		'skirttique/newsletter'       => array(
			'attributes' => array(
				'eyebrow' => $str( __( 'The house list', 'skirttique' ) ),
				'title'   => $str( __( 'First to every collection', 'skirttique' ) ),
				'promise' => $str( __( 'New pieces, private previews and the journal — a few letters a season, nothing more.', 'skirttique' ) ),
			),
			'render'     => static fn ( array $a ): string => Components\newsletter_band(
				array(
					'eyebrow' => $a['eyebrow'],
					'title'   => $a['title'],
					'promise' => $a['promise'],
				)
			),
		),

		'skirttique/faq'              => array(
			'attributes' => array(
				'eyebrow' => $str(),
				'title'   => $str(),
				'items'   => $str(), // One per line: Question|Answer.
			),
			'render'     => static function ( array $a ): string {
				$items = Components\parse_lines( $a['items'], array( 'q', 'a' ) );
				if ( ! $items ) {
					return '';
				}
				$out  = '<section class="st-section st-faq">';
				$out .= Components\section_head( array( 'eyebrow' => $a['eyebrow'], 'title' => $a['title'] ) );
				$out .= '<div class="st-faq__list">';
				foreach ( $items as $item ) {
					$out .= '<details class="st-panel-fold"><summary>' . esc_html( $item['q'] ) . '</summary>'
						. '<div class="st-panel-fold__body"><p>' . esc_html( $item['a'] ) . '</p></div></details>';
				}
				$out .= '</div></section>';

				return $out;
			},
		),

		'skirttique/stats'            => array(
			'attributes' => array(
				'eyebrow' => $str(),
				'title'   => $str(),
				'items'   => $str(), // One per line: Value|Label.
			),
			'render'     => static fn ( array $a ): string => Components\stats(
				array(
					'eyebrow' => $a['eyebrow'],
					'title'   => $a['title'],
					'items'   => Components\parse_lines( $a['items'], array( 'value', 'label' ) ),
				)
			),
		),

		'skirttique/feature-list'     => array(
			'attributes' => array(
				'eyebrow' => $str(),
				'title'   => $str(),
				'items'   => $str(), // One per line: Title|Description.
			),
			'render'     => static fn ( array $a ): string => Components\feature_list(
				array(
					'eyebrow' => $a['eyebrow'],
					'title'   => $a['title'],
					'items'   => Components\parse_lines( $a['items'], array( 'title', 'text' ) ),
				)
			),
		),

		'skirttique/trust-badges'     => array(
			'attributes' => array(
				'delivery' => $bool( true ),
				'returns'  => $bool( true ),
				'secure'   => $bool( true ),
				'made'     => $bool( false ),
			),
			'render'     => static function ( array $a ): string {
				$badges = array_keys( array_filter( array(
					'delivery' => $a['delivery'],
					'returns'  => $a['returns'],
					'secure'   => $a['secure'],
					'made'     => $a['made'],
				) ) );

				return Components\trust_badges( array( 'badges' => $badges ) );
			},
		),

		'skirttique/gallery'          => array(
			'attributes' => array(
				'imageIds' => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'number' ) ),
				'columns'  => $int( 3 ),
				'zoom'     => $bool( false ),
			),
			'render'     => static fn ( array $a ): string => Components\gallery(
				array(
					'image_ids' => (array) $a['imageIds'],
					'columns'   => (int) $a['columns'],
					'zoom'      => (bool) $a['zoom'],
				)
			),
		),

		'skirttique/video'            => array(
			'attributes' => array(
				'videoUrl' => $str(),
				'posterId' => $int(),
				'ambient'  => $bool( false ),
				'caption'  => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\video(
				array(
					'video_url' => $a['videoUrl'],
					'poster_id' => (int) $a['posterId'],
					'ambient'   => (bool) $a['ambient'],
					'caption'   => $a['caption'],
				)
			),
		),

		'skirttique/pricing'          => array(
			'attributes' => array(
				'eyebrow' => $str(),
				'title'   => $str(),
				'items'   => $str(), // One per line: Tier|Price|Description.
			),
			'render'     => static fn ( array $a ): string => Components\pricing(
				array(
					'eyebrow' => $a['eyebrow'],
					'title'   => $a['title'],
					'items'   => Components\parse_lines( $a['items'], array( 'tier', 'price', 'text' ) ),
				)
			),
		),

		'skirttique/breadcrumbs'      => array(
			'attributes' => array(),
			'render'     => static fn (): string => Components\breadcrumbs(),
		),

		'skirttique/featured-collection' => array(
			'attributes' => array(
				'eyebrow'  => $str( __( 'Collection in focus', 'skirttique' ) ),
				'slug'     => $str(), // Blank = first collection with editorial meta.
				'ctaLabel' => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\featured_collection(
				array(
					'eyebrow'   => $a['eyebrow'],
					'slug'      => $a['slug'],
					'cta_label' => $a['ctaLabel'],
				)
			),
		),

		'skirttique/featured-product' => array(
			'attributes' => array(
				'eyebrow'   => $str( __( 'The featured piece', 'skirttique' ) ),
				'productId' => $int(), // 0 = the newest piece.
				'ctaLabel'  => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\featured_product(
				array(
					'eyebrow'    => $a['eyebrow'],
					'product_id' => (int) $a['productId'],
					'cta_label'  => $a['ctaLabel'],
				)
			),
		),

		'skirttique/lookbook'         => array(
			'attributes' => array(
				'eyebrow'    => $str( __( 'The lookbook', 'skirttique' ) ),
				'lookbookId' => $int(), // 0 = the latest published lookbook.
				'ctaLabel'   => $str(),
			),
			'render'     => static fn ( array $a ): string => Components\lookbook_feature(
				array(
					'eyebrow'     => $a['eyebrow'],
					'lookbook_id' => (int) $a['lookbookId'],
					'cta_label'   => $a['ctaLabel'],
				)
			),
		),

		'skirttique/instagram'        => array(
			'attributes' => array(
				'eyebrow'  => $str( __( 'On Instagram', 'skirttique' ) ),
				'title'    => $str( __( 'The house, worn', 'skirttique' ) ),
				'url'      => $str(), // Blank = the house profile (House Settings → Social).
				'imageIds' => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'number' ) ),
			),
			'render'     => static fn ( array $a ): string => Components\instagram(
				array(
					'eyebrow'   => $a['eyebrow'],
					'title'     => $a['title'],
					'url'       => $a['url'],
					'image_ids' => (array) $a['imageIds'],
				)
			),
		),
	);
}

/**
 * Register the category and every block in the manifest.
 */
function register(): void {
	foreach ( manifest() as $name => $config ) {
		register_block_type(
			$name,
			array(
				'api_version'     => 3,
				'attributes'      => $config['attributes'],
				'render_callback' => static function ( array $attributes ) use ( $config, $name ): string {
					// Fill defaults (client omits unchanged attributes).
					foreach ( manifest()[ $name ]['attributes'] as $key => $schema ) {
						if ( ! array_key_exists( $key, $attributes ) ) {
							$attributes[ $key ] = $schema['default'] ?? '';
						}
					}

					return ( $config['render'] )( $attributes );
				},
				'supports'        => array( 'html' => false ),
			)
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\\register' );

/**
 * "Skirttique" block category, listed first.
 *
 * @param array<int, array<string, string>> $categories Existing categories.
 * @return array<int, array<string, string>>
 */
function category( array $categories ): array {
	array_unshift(
		$categories,
		array(
			'slug'  => 'skirttique',
			'title' => __( 'Skirttique', 'skirttique' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', __NAMESPACE__ . '\\category' );

/**
 * Editor bundle: client-side registration + inspector fields + previews.
 */
function editor_assets(): void {
	$asset_file = SKIRTTIQUE_DIR . '/build/editor.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'skirttique-editor',
		SKIRTTIQUE_URI . '/build/editor.js',
		$asset['dependencies'],
		(string) $asset['version'],
		array( 'in_footer' => true )
	);
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\editor_assets' );
