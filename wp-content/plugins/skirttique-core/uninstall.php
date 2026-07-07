<?php
/**
 * Uninstall handler for Skirttique Core.
 *
 * Removes the operational data the plugin owns — stored form submissions
 * (PII), the owner's settings, cached integration data, the retention
 * cron and customer wishlists. Editorial term/product meta the owner
 * authored through the plugin's fields is deliberately preserved (see the
 * note below): it is content, not plugin bookkeeping.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Stop the retention cron the Privacy service scheduled.
wp_clear_scheduled_hook( 'skirttique_privacy_prune' );

// Every option the plugin owns — the stored personal data (client-care
// messages, bespoke requests, the house list), the owner's settings, and
// cached integration data. (Literals mirror the Services\* constants; the
// autoloader is not registered during uninstall, so they are spelled out.)
$skirttique_options = array(
	'skirttique_contact_messages', // ContactMessages::OPTION (PII).
	'skirttique_bespoke_requests', // BespokeRequests::OPTION (PII).
	'skirttique_house_list',       // Newsletter::OPTION (PII).
	'skirttique_house',            // HouseContent::OPTION.
	'skirttique_currency_rates',   // Currency::RATES_OPTION.
	'skirttique_ig_feed_last',     // InstagramFeed::LAST_GOOD.
);
foreach ( $skirttique_options as $skirttique_option ) {
	delete_option( $skirttique_option );
}

// Cached Instagram feed (transient) and every customer's wishlist.
delete_transient( 'skirttique_ig_feed' );
delete_metadata( 'user', 0, 'skirttique_wishlist', '', true );

// Deliberately preserved: the editorial term/product meta authored through
// the plugin's fields — collection story/hero (st_collection_story,
// st_collection_hero_id) and product story/fabric/care (_st_story,
// _st_fabric, _st_care) — is the owner's content, so it survives uninstall
// and a reinstall finds it intact.
