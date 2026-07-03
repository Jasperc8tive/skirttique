<?php
/**
 * Stage 20: dress each collection with an editorial story
 * (st_collection_story term-meta — the landing-page introduction).
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/seed-collection-stories.php
 *
 * Idempotent and non-destructive: a term that already has a story is
 * skipped, so owner edits in Products → Categories always win. Shipped
 * copy only — replace in wp-admin whenever the house prefers its own
 * words. Landing heroes are deliberately NOT seeded: the landing falls
 * back to the category thumbnail until a dedicated wide hero is chosen.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$skirttique_stories = array(
	'maxi'            => 'Floor-sweeping silhouettes cut to move — the long line the house is known for, in cloths that carry from tarmac to terrace.',
	'midi'            => 'The working length. Sharp enough for the boardroom, easy enough for everything after — cut to sit exactly where it should.',
	'limited-edition' => 'Numbered runs that do not return. When the cloth is gone, the piece is retired — kept by the women who found it first.',
	'bespoke'         => 'Cut to your measure in the Lagos atelier. One client, one pattern, eight weeks — a skirt that exists nowhere else.',
);

foreach ( $skirttique_stories as $skirttique_slug => $skirttique_story ) {
	$skirttique_term = get_term_by( 'slug', $skirttique_slug, 'product_cat' );
	if ( ! $skirttique_term instanceof WP_Term ) {
		WP_CLI::warning( "No collection '{$skirttique_slug}' — skipped." );
		continue;
	}

	$skirttique_existing = trim( (string) get_term_meta( $skirttique_term->term_id, 'st_collection_story', true ) );
	if ( '' !== $skirttique_existing ) {
		WP_CLI::log( "{$skirttique_term->name}: story already set — kept." );
		continue;
	}

	update_term_meta( $skirttique_term->term_id, 'st_collection_story', $skirttique_story );
	WP_CLI::success( "{$skirttique_term->name}: story seeded." );
}
