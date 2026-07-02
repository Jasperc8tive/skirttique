<?php
/**
 * Stage 13: Rank Math configuration as code.
 *
 * Everything the setup wizard would do, captured so production gets the
 * identical configuration:
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/configure-seo.php
 *
 * Idempotent — merges into existing options, never clobbers unrelated
 * keys an admin may have set since.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------
// 1. Modules — only what the site uses. Analytics/Content-AI and the
//    rest of the promotional modules stay off (each adds admin weight;
//    analytics can be enabled later when Search Console is connected).
// ---------------------------------------------------------------------
update_option( 'rank_math_modules', array( 'sitemap', 'rich-snippet', 'woocommerce', 'seo-analysis' ) );

// Silence the "connect your account" onboarding nags.
update_option( 'rank_math_registration_skip', 1 );
add_option( 'rank_math_wizard_completed', 1 );

// ---------------------------------------------------------------------
// 2. Titles, meta, social, knowledge graph.
// ---------------------------------------------------------------------
$skirttique_titles = (array) get_option( 'rank-math-options-titles', array() );

$skirttique_hero_id = 0;
$skirttique_house   = (array) get_option( 'skirttique_house', array() );
if ( ! empty( $skirttique_house['hero_image_id'] ) ) {
	$skirttique_hero_id = absint( $skirttique_house['hero_image_id'] );
}

$skirttique_titles = array_merge(
	$skirttique_titles,
	array(
		'title_separator'          => '—',

		// Homepage.
		'homepage_title'           => 'Skirttique — Luxury Midi & Maxi Skirts',
		'homepage_description'     => 'A luxury fashion house for midi and maxi skirts — cut for movement, made in limited runs in Lagos, delivered worldwide.',
		'homepage_custom_robots'   => 'off',

		// Products: name — Skirttique, description from the short description.
		'pt_product_title'         => '%title% %sep% %sitename%',
		'pt_product_description'   => '%excerpt%',
		'pt_product_default_rich_snippet' => 'product',

		// Collections.
		'tax_product_cat_title'       => '%term% %sep% Skirttique Collections',
		'tax_product_cat_description' => '%term_description%',
		'remove_product_cat_snippet_data' => 'off',

		// Knowledge graph — the house itself.
		'knowledgegraph_type'      => 'company',
		'knowledgegraph_name'      => 'Skirttique',
		'website_name'             => 'Skirttique',
		'knowledgegraph_logo_id'   => $skirttique_hero_id, // Placeholder until a true logo asset exists.
		'open_graph_image_id'      => $skirttique_hero_id,

		// Social cards.
		'twitter_card_type'        => 'summary_large_image',
	)
);

update_option( 'rank-math-options-titles', $skirttique_titles );

// ---------------------------------------------------------------------
// 3. Utility pages: noindex,follow + out of the sitemap. Shoppers reach
//    these by acting, never by searching.
// ---------------------------------------------------------------------
$skirttique_utility_ids = array_filter(
	array(
		(int) get_option( 'woocommerce_cart_page_id' ),
		(int) get_option( 'woocommerce_checkout_page_id' ),
		(int) get_option( 'woocommerce_myaccount_page_id' ),
		get_page_by_path( 'saved' ) ? (int) get_page_by_path( 'saved' )->ID : 0,
	)
);

foreach ( $skirttique_utility_ids as $skirttique_page_id ) {
	update_post_meta( $skirttique_page_id, 'rank_math_robots', array( 'noindex', 'follow' ) );
}

$skirttique_sitemap = (array) get_option( 'rank-math-options-sitemap', array() );

$skirttique_sitemap = array_merge(
	$skirttique_sitemap,
	array(
		'exclude_posts'        => implode( ',', $skirttique_utility_ids ),
		'pt_product_sitemap'   => 'on',
		'pt_page_sitemap'      => 'on',
		'tax_product_cat_sitemap' => 'on',
		'include_images'       => 'on',
	)
);

update_option( 'rank-math-options-sitemap', $skirttique_sitemap );

// ---------------------------------------------------------------------
// 4. General: breadcrumb schema support (the theme renders its own
//    trail visually; Rank Math contributes the BreadcrumbList JSON-LD).
// ---------------------------------------------------------------------
$skirttique_general = (array) get_option( 'rank-math-options-general', array() );

$skirttique_general = array_merge(
	$skirttique_general,
	array(
		'breadcrumbs'          => 'on',
		'breadcrumbs_separator' => '/',
		'breadcrumbs_home'      => 'on',
		'breadcrumbs_home_label' => 'Shop',
	)
);

update_option( 'rank-math-options-general', $skirttique_general );

// Rebuild rewrite rules so /sitemap_index.xml resolves immediately.
flush_rewrite_rules();

WP_CLI::success( 'Rank Math configured for Skirttique.' );
