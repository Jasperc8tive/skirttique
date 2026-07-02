<?php
/**
 * Stage 16: production launch preflight.
 *
 * Inspects the live store and reports what is launch-ready, what needs a
 * human decision, and what would block go-live. Read-only — changes
 * nothing. Run on the target environment before flipping DNS:
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/preflight.php
 *
 * FAIL blocks launch; WARN needs review (often expected on a staging
 * copy — e.g. gateways not yet in live mode). Exit is non-zero when any
 * FAIL is present, so CI/deploy scripts can gate on it.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$skirttique_fails = 0;
$skirttique_warns = 0;

/**
 * @param string $status PASS|WARN|FAIL
 */
$check = static function ( $status, $label, $detail = '' ) use ( &$skirttique_fails, &$skirttique_warns ) {
	if ( 'FAIL' === $status ) {
		++$skirttique_fails;
	} elseif ( 'WARN' === $status ) {
		++$skirttique_warns;
	}
	WP_CLI::log( sprintf( '  [%s] %-42s %s', $status, $label, $detail ) );
};

WP_CLI::log( "\n== Environment & indexing ==" );

$check(
	get_option( 'blog_public' ) ? 'PASS' : 'FAIL',
	'Search engine visibility',
	get_option( 'blog_public' ) ? 'indexable' : 'blog_public=0 — site is hidden from search'
);

$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'unknown';
$check( 'production' === $env ? 'PASS' : 'WARN', 'Environment type', $env );

$check(
	get_option( 'permalink_structure' ) ? 'PASS' : 'FAIL',
	'Permalinks',
	get_option( 'permalink_structure' ) ?: 'PLAIN — pretty permalinks required'
);

$check( wp_get_theme()->get_stylesheet() === 'skirttique' ? 'PASS' : 'FAIL', 'Active theme', wp_get_theme()->get_stylesheet() );

WP_CLI::log( "\n== WooCommerce ==" );

$check( class_exists( 'WooCommerce' ) ? 'PASS' : 'FAIL', 'WooCommerce active', class_exists( 'WooCommerce' ) ? WC()->version : 'missing' );

$coming_soon = get_option( 'woocommerce_coming_soon' );
$check( 'yes' === $coming_soon ? 'FAIL' : 'PASS', 'Coming-soon mode', 'yes' === $coming_soon ? 'store is hidden behind coming-soon' : 'off' );

$hpos = get_option( 'woocommerce_custom_orders_table_enabled' );
$check( 'yes' === $hpos ? 'PASS' : 'WARN', 'HPOS (order tables)', 'yes' === $hpos ? 'enabled' : 'legacy post storage' );

WP_CLI::log( "\n== Required pages ==" );

$pages = array(
	'shop'             => (int) get_option( 'woocommerce_shop_page_id' ),
	'cart'             => (int) get_option( 'woocommerce_cart_page_id' ),
	'checkout'         => (int) get_option( 'woocommerce_checkout_page_id' ),
	'my-account'       => (int) get_option( 'woocommerce_myaccount_page_id' ),
);
foreach ( array( 'saved', 'about', 'journal', 'size-guide', 'delivery-returns', 'privacy', 'terms', 'faqs', 'contact' ) as $slug ) {
	$page            = get_page_by_path( $slug );
	$pages[ $slug ] = $page ? (int) $page->ID : 0;
}
foreach ( $pages as $slug => $id ) {
	$ok = $id && 'publish' === get_post_status( $id );
	// Content pages are WARN (can launch, fill later); commerce pages are FAIL.
	$critical = in_array( $slug, array( 'shop', 'cart', 'checkout', 'my-account' ), true );
	$check( $ok ? 'PASS' : ( $critical ? 'FAIL' : 'WARN' ), "Page: {$slug}", $ok ? "#{$id}" : 'missing or draft' );
}

WP_CLI::log( "\n== SEO ==" );

$check( class_exists( 'RankMath' ) ? 'PASS' : 'WARN', 'Rank Math active', class_exists( 'RankMath' ) ? 'yes' : 'not installed' );

$utility_noindex = true;
foreach ( array( 'cart', 'checkout', 'my-account', 'saved' ) as $slug ) {
	$id = 'saved' === $slug ? ( get_page_by_path( 'saved' ) ? get_page_by_path( 'saved' )->ID : 0 ) : (int) get_option( 'woocommerce_' . str_replace( '-', '', $slug === 'my-account' ? 'myaccount' : $slug ) . '_page_id' );
	if ( $id ) {
		$robots = (array) get_post_meta( $id, 'rank_math_robots', true );
		if ( ! in_array( 'noindex', $robots, true ) ) {
			$utility_noindex = false;
		}
	}
}
$check( $utility_noindex ? 'PASS' : 'WARN', 'Utility pages noindexed', $utility_noindex ? 'cart/checkout/account/saved' : 'run configure-seo.php' );

WP_CLI::log( "\n== Payments (currency → gateway) ==" );

$gateways = class_exists( 'WooCommerce' ) ? WC()->payment_gateways()->get_available_payment_gateways() : array();
foreach ( array( 'paystack' => 'NGN', 'stripe' => 'international' ) as $gw => $scope ) {
	$enabled = isset( $gateways[ $gw ] );
	$check( $enabled ? 'PASS' : 'WARN', "Gateway: {$gw} ({$scope})", $enabled ? 'enabled' : 'not enabled/configured — needs live keys' );
}

WP_CLI::log( "\n== Multi-currency ==" );

$rates        = get_option( 'skirttique_currency_rates', array() );
$rates        = is_array( $rates ) ? $rates : array();
$has_real     = ! empty( $rates );
$check( $has_real ? 'PASS' : 'WARN', 'Currency rates', $has_real ? implode( ', ', array_keys( $rates ) ) . ' set' : 'using SHIPPED PLACEHOLDER rates — set real rates before charging' );

WP_CLI::log( "\n== Brand media ==" );

$house   = (array) get_option( 'skirttique_house', array() );
$hero_id = isset( $house['hero_image_id'] ) ? absint( $house['hero_image_id'] ) : 0;
$check( $hero_id && wp_attachment_is_image( $hero_id ) ? 'PASS' : 'WARN', 'Homepage hero image', $hero_id ? "#{$hero_id}" : 'falls back to shipped editorial placeholder' );

$cats_with_thumb = 0;
$terms           = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$terms           = is_wp_error( $terms ) ? array() : $terms;
foreach ( $terms as $term ) {
	if ( absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) ) ) {
		++$cats_with_thumb;
	}
}
$check( $cats_with_thumb > 0 ? 'PASS' : 'WARN', 'Collection thumbnails', "{$cats_with_thumb} of " . count( $terms ) . ' categories dressed' );

WP_CLI::log( "\n== Housekeeping ==" );

$default_post = get_post( 1 );
$check( ( ! $default_post || 'Hello world!' !== $default_post->post_title ) ? 'PASS' : 'WARN', 'Default content removed', 'Hello World / Sample Page' );

WP_CLI::log( "\n" . str_repeat( '─', 60 ) );
WP_CLI::log( sprintf( 'Preflight: %d blocking (FAIL), %d to review (WARN).', $skirttique_fails, $skirttique_warns ) );

if ( $skirttique_fails > 0 ) {
	WP_CLI::error( 'Not ready to launch — resolve the FAIL items above.' );
}

WP_CLI::success( 0 === $skirttique_warns ? 'Cleared for launch.' : 'No blockers. Review the WARN items, then launch.' );
