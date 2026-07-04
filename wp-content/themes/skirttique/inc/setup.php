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
 * Whether this request resolved to the coming-soon splash
 * (WooCommerce's site-visibility mode swaps the block template in; the
 * theme provides templates/coming-soon.html). In a block theme the
 * included FILE is always template-canvas.php — the chosen template is
 * identified by $_wp_current_template_id ('skirttique//coming-soon'),
 * set during resolution, before wp_head prints any robots meta.
 * (Woo's swap does not populate $_wp_current_template_slug.)
 */
function is_coming_soon(): bool {
	return str_ends_with( (string) ( $GLOBALS['_wp_current_template_id'] ?? '' ), '//coming-soon' );
}

/**
 * The splash must never be indexed — it is a placeholder, not the
 * store. Late priority so it also overrides the SEO plugin's robots.
 *
 * @param array<string, bool|string> $robots Robots directives.
 * @return array<string, bool|string>
 */
function coming_soon_robots( array $robots ): array {
	return is_coming_soon()
		? array(
			'noindex'  => true,
			'nofollow' => true,
		)
		: $robots;
}
add_filter( 'wp_robots', __NAMESPACE__ . '\\coming_soon_robots', PHP_INT_MAX );

/**
 * Rank Math prints its own robots meta (core's wp_robots never renders
 * while it is active) — mirror the splash noindex through its filter.
 *
 * @param array<string, string> $robots Rank Math directives.
 * @return array<string, string>
 */
function coming_soon_robots_rank_math( array $robots ): array {
	return is_coming_soon()
		? array(
			'noindex'  => 'noindex',
			'nofollow' => 'nofollow',
		)
		: $robots;
}
add_filter( 'rank_math/frontend/robots', __NAMESPACE__ . '\\coming_soon_robots_rank_math', PHP_INT_MAX );

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
