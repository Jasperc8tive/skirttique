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
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );

/**
 * Preload the two critical brand fonts to protect LCP and CLS.
 *
 * @param array<int, array<string, string>> $urls          URLs to hint.
 * @param string                            $relation_type Relation type.
 * @return array<int, array<string, string>>
 */
function preload_fonts( array $urls, string $relation_type ): array {
	if ( 'preload' !== $relation_type ) {
		return $urls;
	}

	foreach ( array( 'laluxes-regular.woff2', 'garet-book.woff2' ) as $font ) {
		$urls[] = array(
			'href'        => SKIRTTIQUE_URI . '/assets/fonts/' . $font,
			'as'          => 'font',
			'type'        => 'font/woff2',
			'crossorigin' => 'anonymous',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', __NAMESPACE__ . '\\preload_fonts', 10, 2 );
