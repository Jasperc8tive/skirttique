<?php
/**
 * Uninstall handler for Skirttique Core.
 *
 * Nothing to remove yet — the skeleton stores no data. As services gain
 * persistence (wishlist user meta, settings), their cleanup lands here.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Stop the retention cron the Privacy service scheduled.
wp_clear_scheduled_hook( 'skirttique_privacy_prune' );

// Remove the stored personal data — client-care messages, bespoke
// requests, and the house list — so no PII outlives the plugin. (These
// literals mirror the Services\* OPTION constants; the autoloader is not
// registered during uninstall, so they are spelled out here.)
foreach ( array( 'skirttique_contact_messages', 'skirttique_bespoke_requests', 'skirttique_house_list' ) as $skirttique_pii_option ) {
	delete_option( $skirttique_pii_option );
}
