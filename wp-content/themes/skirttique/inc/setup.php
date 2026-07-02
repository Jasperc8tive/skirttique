<?php
/**
 * Theme setup: supports, editor styles, pattern categories.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\Setup;

/**
 * Register theme supports.
 */
function setup(): void {
	// Block themes get most supports implicitly; these are the explicit extras.
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/tokens.css', 'build/index.css' ) );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	// Classic-template surfaces WooCommerce still renders (My Account,
	// order emails) — also unlocks theme overrides under /woocommerce/.
	add_theme_support( 'woocommerce' );

	load_theme_textdomain( 'skirttique', SKIRTTIQUE_DIR . '/languages' );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Register the theme's block pattern category.
 */
function register_pattern_category(): void {
	register_block_pattern_category(
		'skirttique',
		array(
			'label'       => __( 'Skirttique', 'skirttique' ),
			'description' => __( 'Editorial sections from the Skirttique design system.', 'skirttique' ),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_pattern_category' );
