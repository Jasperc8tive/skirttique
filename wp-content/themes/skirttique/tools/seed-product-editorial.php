<?php
/**
 * Stage 21 DEV-ONLY demo data: editorial meta on a few demo products and
 * two sample reviews, so the PDP v2 surfaces can be verified. Like
 * seed-demo-catalog.php this is placeholder content for the local
 * environment — NEVER run on production (real fabric, care, and reviews
 * come from the house and its clients).
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/seed-product-editorial.php
 *
 * Idempotent: existing meta is kept; reviews are keyed by author+product.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'production' === wp_get_environment_type() ) {
	WP_CLI::error( 'Demo seed — refusing to run in production.' );
}

update_option( 'woocommerce_enable_reviews', 'yes' );

// Editorial meta: one fully dressed piece, one fabric-only (to verify
// the care fallback), the rest untouched (panels hide when blank).
$skirttique_editorial = array(
	'Yewande Atelier Maxi' => array(
		'_st_fabric' => 'Silk-blend crepe with a full cotton-voile lining. Deadstock cloth, bought by the bolt — when it is gone, it is gone.',
		'_st_care'   => 'Dry-clean only. Steam the pleats rather than pressing them, and hang from the waistband to rest.',
		'_st_story'  => 'Named for the first client who commissioned it: a barrister who wanted a skirt that could hold a courtroom. The pattern has not changed since.',
	),
	'Adaeze Silk Maxi'     => array(
		'_st_fabric' => '100% mulberry silk, 22 momme, French-seamed throughout.',
	),
);

foreach ( $skirttique_editorial as $skirttique_name => $skirttique_meta ) {
	$skirttique_found = wc_get_products( array( 'name' => $skirttique_name, 'limit' => 1 ) );
	$skirttique_piece = $skirttique_found[0] ?? null;
	if ( ! $skirttique_piece ) {
		WP_CLI::warning( "No product '{$skirttique_name}' — skipped." );
		continue;
	}

	foreach ( $skirttique_meta as $skirttique_key => $skirttique_value ) {
		if ( '' === trim( (string) $skirttique_piece->get_meta( $skirttique_key ) ) ) {
			$skirttique_piece->update_meta_data( $skirttique_key, $skirttique_value );
		}
	}
	$skirttique_piece->save();
	WP_CLI::success( "{$skirttique_name}: editorial meta set." );
}

// Ensure reviews can be left: open comments on all products.
foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'ids' ) ) as $skirttique_pid ) {
	wp_update_post(
		array(
			'ID'             => $skirttique_pid,
			'comment_status' => 'open',
		)
	);
}
WP_CLI::log( 'Comments open on all products.' );

// Two demo reviews on Chidera A-Line Midi.
$skirttique_found  = wc_get_products( array( 'name' => 'Chidera A-Line Midi', 'limit' => 1 ) );
$skirttique_target = $skirttique_found[0] ?? null;

if ( $skirttique_target ) {
	$skirttique_reviews = array(
		array(
			'author'  => 'Amara O.',
			'email'   => 'amara.demo@example.com',
			'rating'  => 5,
			'content' => 'Wore it to a client dinner straight off the flight — not a crease. The waist sits exactly where the size guide said it would.',
		),
		array(
			'author'  => 'Tolu A.',
			'email'   => 'tolu.demo@example.com',
			'rating'  => 4,
			'content' => 'Beautiful weight to the cloth. I am between sizes and took the larger as advised; a belt makes it perfect.',
		),
	);

	foreach ( $skirttique_reviews as $skirttique_review ) {
		$skirttique_existing = get_comments(
			array(
				'post_id'      => $skirttique_target->get_id(),
				'author_email' => $skirttique_review['email'],
				'count'        => true,
			)
		);
		if ( $skirttique_existing > 0 ) {
			WP_CLI::log( "Review by {$skirttique_review['author']} exists — kept." );
			continue;
		}

		$skirttique_comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $skirttique_target->get_id(),
				'comment_author'       => $skirttique_review['author'],
				'comment_author_email' => $skirttique_review['email'],
				'comment_content'      => $skirttique_review['content'],
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		if ( $skirttique_comment_id ) {
			add_comment_meta( $skirttique_comment_id, 'rating', $skirttique_review['rating'], true );
			WP_CLI::success( "Review by {$skirttique_review['author']} added." );
		}
	}

	// Recalculate the product's stored rating aggregates.
	WC_Comments::clear_transients( $skirttique_target->get_id() );
	$skirttique_target = wc_get_product( $skirttique_target->get_id() );
	WP_CLI::log( 'Chidera average rating now: ' . $skirttique_target->get_average_rating() . " ({$skirttique_target->get_review_count()} reviews)." );
} else {
	WP_CLI::warning( 'Chidera A-Line Midi not found — reviews skipped.' );
}
