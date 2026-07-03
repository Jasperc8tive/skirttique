<?php
/**
 * Stage 18: content-architecture setup.
 *
 * - Seeds the seven Journal categories (core posts ARE the Journal).
 * - Wires reading settings: a blank Home page as page_on_front (the
 *   front-page.html template renders regardless; this keeps WordPress's
 *   query state coherent) and the Journal page as page_for_posts.
 * - Creates one sample lookbook (block-composed) and one sample journal
 *   post so the new types render against real content.
 *
 * Idempotent — safe to re-run. After first run, flush rewrites in a
 * SEPARATE request (CPT rules register on the next boot):
 *   npx wp-env run cli wp rewrite flush
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------
// 1. Journal categories.
// ---------------------------------------------------------------------
$skirttique_journal_cats = array(
	'Fashion', 'Styling', 'Behind the Scenes', 'Campaigns', 'Guides', 'Care Tips', 'Lifestyle',
);

foreach ( $skirttique_journal_cats as $skirttique_cat ) {
	if ( ! get_term_by( 'name', $skirttique_cat, 'category' ) ) {
		wp_insert_term( $skirttique_cat, 'category' );
		WP_CLI::log( "category: {$skirttique_cat}" );
	}
}

// ---------------------------------------------------------------------
// 2. Reading settings — / stays the homepage, /journal/ lists posts.
// ---------------------------------------------------------------------
$skirttique_home = get_page_by_path( 'home' );
if ( ! $skirttique_home ) {
	$skirttique_home_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'Home',
			'post_name'   => 'home',
			'post_status' => 'publish',
		)
	);
	WP_CLI::log( "home page: #{$skirttique_home_id}" );
} else {
	$skirttique_home_id = $skirttique_home->ID;
}

$skirttique_journal = get_page_by_path( 'journal' );
if ( $skirttique_journal ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $skirttique_home_id );
	update_option( 'page_for_posts', $skirttique_journal->ID );
	WP_CLI::log( "reading: front=#{$skirttique_home_id} posts=#{$skirttique_journal->ID}" );
}

// ---------------------------------------------------------------------
// 3. Sample lookbook (uses the sideloaded editorial attachments).
// ---------------------------------------------------------------------
if ( ! get_page_by_path( 'collection-i-the-field', OBJECT, 'lookbook' ) ) {
	$skirttique_lookbook_id = wp_insert_post(
		array(
			'post_type'    => 'lookbook',
			'post_title'   => 'Collection I — The Field',
			'post_name'    => 'collection-i-the-field',
			'post_status'  => 'publish',
			'post_excerpt' => 'The first collection, photographed where it moves best.',
			'post_content' => '<!-- wp:skirttique/hero {"eyebrow":"Lookbook","statement":"Collection I — The Field","sub":"Photographed where the pieces move best.","isBanner":false} /-->

<!-- wp:skirttique/gallery {"imageIds":[41,42,43,44,46],"columns":2} /-->

<!-- wp:skirttique/cta {"statement":"The pieces, in the shop.","ctaLabel":"Shop Collection I","ctaUrl":"/shop/"} /-->',
		)
	);
	if ( ! is_wp_error( $skirttique_lookbook_id ) ) {
		set_post_thumbnail( $skirttique_lookbook_id, 45 );
		WP_CLI::log( "lookbook: #{$skirttique_lookbook_id}" );
	}
}

// ---------------------------------------------------------------------
// 4. Sample journal post.
// ---------------------------------------------------------------------
if ( ! get_page_by_path( 'caring-for-silk', OBJECT, 'post' ) ) {
	$skirttique_care = get_term_by( 'name', 'Care Tips', 'category' );

	$skirttique_post_id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_title'    => 'Caring for Silk',
			'post_name'     => 'caring-for-silk',
			'post_status'   => 'publish',
			'post_excerpt'  => 'Silk rewards gentleness — a short guide to keeping it falling right.',
			'post_category' => $skirttique_care ? array( $skirttique_care->term_id ) : array(),
			'post_content'  => '<!-- wp:paragraph --><p>Silk rewards gentleness. Cold water, a mild soap, and never a wring — press the water out between two towels and hang away from direct sun.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Steam rather than iron where you can. If you must iron, inside out, low heat, and keep the plate moving.</p><!-- /wp:paragraph -->',
		)
	);
	if ( ! is_wp_error( $skirttique_post_id ) ) {
		WP_CLI::log( "journal post: #{$skirttique_post_id}" );
	}
}

WP_CLI::success( 'Content architecture seeded.' );
