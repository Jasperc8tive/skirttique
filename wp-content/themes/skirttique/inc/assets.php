<?php
/**
 * Front-end asset loading: tokens, compiled bundle, font preloads.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\Assets;

/**
 * Enqueue design tokens and the compiled theme bundle.
 */
function enqueue(): void {
	wp_enqueue_style(
		'skirttique-tokens',
		SKIRTTIQUE_URI . '/assets/css/tokens.css',
		array(),
		SKIRTTIQUE_VERSION
	);

	$asset_file = SKIRTTIQUE_DIR . '/build/index.asset.php';
	if ( file_exists( $asset_file ) ) {
		$asset = require $asset_file;

		wp_enqueue_style(
			'skirttique-main',
			SKIRTTIQUE_URI . '/build/index.css',
			array( 'skirttique-tokens' ),
			(string) $asset['version']
		);

		wp_enqueue_script(
			'skirttique-main',
			SKIRTTIQUE_URI . '/build/index.js',
			$asset['dependencies'],
			(string) $asset['version'],
			array( 'strategy' => 'defer' )
		);

		// Config the bundle needs: login state, the wishlist nonce
		// (action shared with Skirttique\Core\Services\Wishlist), and the
		// owner's Experience switches (House Settings; '' = never saved =
		// enabled — only an explicit 'off' disables).
		$house = (array) get_option( 'skirttique_house', array() );

		wp_add_inline_script(
			'skirttique-main',
			'window.stConfig = ' . (string) wp_json_encode(
				array(
					'loggedIn'      => is_user_logged_in(),
					'wishlistNonce' => wp_create_nonce( 'skirttique_wishlist' ),
					'motion'        => array(
						'transitions' => 'off' !== ( $house['motion_transitions'] ?? '' ),
						'parallax'    => 'off' !== ( $house['motion_parallax'] ?? '' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );

/**
 * Preload the critical resources: both brand fonts everywhere, and the
 * hero image on the front page (it is the LCP element).
 *
 * Uses `wp_preload_resources` (WP 6.1+) — NOT `wp_resource_hints`,
 * which silently ignores the `preload` relation (Stage 12 audit found
 * the original hints never rendered).
 *
 * @param array<int, array<string, string>> $resources Resources to preload.
 * @return array<int, array<string, string>>
 */
function preload_resources( array $resources ): array {
	foreach ( array( 'laluxes-regular.woff2', 'garet-book.woff2' ) as $font ) {
		$resources[] = array(
			'href'        => SKIRTTIQUE_URI . '/assets/fonts/' . $font,
			'as'          => 'font',
			'type'        => 'font/woff2',
			'crossorigin' => 'anonymous',
		);
	}

	if ( is_front_page() ) {
		$house   = (array) get_option( 'skirttique_house', array() );
		$hero_id = isset( $house['hero_image_id'] ) ? absint( $house['hero_image_id'] ) : 0;

		if ( $hero_id ) {
			$src = wp_get_attachment_image_src( $hero_id, 'full' );
			if ( $src ) {
				$preload = array(
					'href'          => $src[0],
					'as'            => 'image',
					'fetchpriority' => 'high',
				);

				$srcset = wp_get_attachment_image_srcset( $hero_id, 'full' );
				if ( $srcset ) {
					$preload['imagesrcset'] = $srcset;
					$preload['imagesizes']  = '100vw';
				}

				$resources[] = $preload;
			}
		}
	}

	return $resources;
}
add_filter( 'wp_preload_resources', __NAMESPACE__ . '\\preload_resources' );

/**
 * Without JavaScript, drape.ts never adds `.is-visible`, and the CSS
 * clip-path that starts every `.st-drape` hidden would stay hidden
 * forever. This is the no-JS safety net — the reduced-motion media
 * query in _drape.scss covers the JS-present case.
 */
function noscript_drape_fallback(): void {
	echo '<noscript><style>.st-drape > * { clip-path: none !important; transform: none !important; }</style></noscript>';
}
add_action( 'wp_head', __NAMESPACE__ . '\\noscript_drape_fallback' );
