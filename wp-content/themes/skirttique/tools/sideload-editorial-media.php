<?php
/**
 * Stage 12: bring the editorial placeholder photography home.
 *
 * Sideloads the six verified Unsplash images into the media library as
 * WebP, then wires them where the CMS expects owner media: the four
 * product_cat thumbnails, and the House Settings hero + philosophy
 * image ids. After this runs, every image on the site is served
 * locally with WordPress srcset/sizes — the hotlinked URLs remain in
 * the patterns only as last-resort fallbacks.
 *
 * Run once inside the environment:
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/sideload-editorial-media.php
 *
 * Idempotent: skips anything already wired.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Download one Unsplash photo as WebP and create an attachment.
 *
 * @param string $photo_id Unsplash photo id.
 * @param string $title    Attachment title.
 * @param int    $width    Longest-edge pixel width to request.
 * @return int Attachment id (0 on failure).
 */
function skirttique_sideload_unsplash( $photo_id, $title, $width ) {
	// Reuse an existing copy (idempotence) — matched on stored source id.
	$existing = get_posts(
		array(
			'post_type'   => 'attachment',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_skirttique_source',
			'meta_value'  => $photo_id,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$url = sprintf( 'https://images.unsplash.com/%s?q=80&w=%d&fit=crop&fm=webp', $photo_id, $width );

	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		WP_CLI::warning( "download failed for {$photo_id}: " . $tmp->get_error_message() );
		return 0;
	}

	$file = array(
		'name'     => sanitize_title( $title ) . '.webp',
		'tmp_name' => $tmp,
	);

	$id = media_handle_sideload( $file, 0, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		WP_CLI::warning( "sideload failed for {$photo_id}: " . $id->get_error_message() );
		return 0;
	}

	update_post_meta( $id, '_skirttique_source', $photo_id );

	return (int) $id;
}

// ---------------------------------------------------------------------
// 1. Category thumbnails (Products → Categories owns collection imagery).
// ---------------------------------------------------------------------
$skirttique_category_media = array(
	'maxi'            => array( 'photo-1762342676026-09e25daaf607', 'Maxi collection — waterfront pleats', 1200 ),
	'midi'            => array( 'photo-1722486245824-7bb0ff9827dc', 'Midi collection — garden light', 1200 ),
	'limited-edition' => array( 'photo-1688582949975-98a0750d7505', 'Limited edition — the hallway', 1200 ),
	'bespoke'         => array( 'photo-1604648145659-29f52af5cf7f', 'Bespoke — sand tones on stone steps', 1200 ),
);

foreach ( $skirttique_category_media as $skirttique_slug => $skirttique_spec ) {
	$skirttique_term = get_term_by( 'slug', $skirttique_slug, 'product_cat' );
	if ( ! $skirttique_term ) {
		WP_CLI::warning( "category {$skirttique_slug} missing" );
		continue;
	}

	if ( absint( get_term_meta( $skirttique_term->term_id, 'thumbnail_id', true ) ) ) {
		WP_CLI::log( "category {$skirttique_slug}: thumbnail already set" );
		continue;
	}

	$skirttique_id = skirttique_sideload_unsplash( $skirttique_spec[0], $skirttique_spec[1], $skirttique_spec[2] );
	if ( $skirttique_id ) {
		update_term_meta( $skirttique_term->term_id, 'thumbnail_id', $skirttique_id );
		WP_CLI::log( "category {$skirttique_slug}: thumbnail {$skirttique_id}" );
	}
}

// ---------------------------------------------------------------------
// 2. House Settings hero + philosophy images (only when the owner has
//    not chosen their own).
// ---------------------------------------------------------------------
$skirttique_house = (array) get_option( 'skirttique_house', array() );

if ( empty( $skirttique_house['hero_image_id'] ) ) {
	$skirttique_hero = skirttique_sideload_unsplash( 'photo-1693259317871-6dfeb116c763', 'Hero — white dress in the field', 1800 );
	if ( $skirttique_hero ) {
		$skirttique_house['hero_image_id'] = $skirttique_hero;
		WP_CLI::log( "hero image: {$skirttique_hero}" );
	}
}

if ( empty( $skirttique_house['philosophy_image_id'] ) ) {
	$skirttique_phil = skirttique_sideload_unsplash( 'photo-1774380255851-72e8a36d7a59', 'Philosophy — flowing colour', 1200 );
	if ( $skirttique_phil ) {
		$skirttique_house['philosophy_image_id'] = $skirttique_phil;
		WP_CLI::log( "philosophy image: {$skirttique_phil}" );
	}
}

update_option( 'skirttique_house', $skirttique_house );

WP_CLI::success( 'Editorial media wired locally.' );
