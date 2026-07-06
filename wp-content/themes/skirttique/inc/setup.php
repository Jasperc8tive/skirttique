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
 * Rank Math carries the store's entire discretionary SEO surface — page
 * titles, meta descriptions, Open Graph / Twitter cards, the XML sitemap
 * and the Organization + BreadcrumbList schema graph (see
 * tools/configure-seo.php and docs/seo.md). WooCommerce core still emits
 * Product/Offer schema without it, but everything else silently vanishes.
 * A theme cannot hard-require a plugin, so it warns loudly in wp-admin
 * when the dependency is missing — the same condition preflight.php flags.
 */
function seo_dependency_notice(): void {
	if ( class_exists( 'RankMath' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'Skirttique SEO is inactive.', 'skirttique' ),
		esc_html__( 'Rank Math is not active — page titles, meta descriptions, social share cards, the XML sitemap and the Organization/Breadcrumb schema are all switched off until it is installed and activated (product structured data still works). Activate Rank Math, then apply tools/configure-seo.php.', 'skirttique' )
	);
}
add_action( 'admin_notices', __NAMESPACE__ . '\\seo_dependency_notice' );

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
