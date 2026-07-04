<?php
/**
 * Stage 24: commerce-experience data — a free-shipping threshold on the
 * NG zone (PLACEHOLDER amount, like the flat rates), cross-sells on the
 * demo pairs (feeds the block cart's cross-sells), and the Custom
 * Orders page composed from the block library.
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/seed-commerce-experience.php
 *
 * Idempotent throughout; the page composition is skipped once it holds
 * any Skirttique block, so owner edits win.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Free shipping on the NG zone (threshold ₦150,000 — placeholder).    */
/* ------------------------------------------------------------------ */
$skirttique_ng_zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $skirttique_zone_data ) {
	$skirttique_zone = new WC_Shipping_Zone( $skirttique_zone_data['id'] );
	foreach ( $skirttique_zone->get_zone_locations() as $skirttique_location ) {
		if ( 'NG' === $skirttique_location->code ) {
			$skirttique_ng_zone = $skirttique_zone;
			break 2;
		}
	}
}

if ( ! $skirttique_ng_zone ) {
	WP_CLI::warning( 'No NG shipping zone found — free-shipping threshold skipped.' );
} else {
	$skirttique_has_free = false;
	foreach ( $skirttique_ng_zone->get_shipping_methods() as $skirttique_method ) {
		if ( 'free_shipping' === $skirttique_method->id ) {
			$skirttique_has_free = true;
			break;
		}
	}

	if ( $skirttique_has_free ) {
		WP_CLI::log( 'NG zone already has free shipping — kept.' );
	} else {
		$skirttique_instance = $skirttique_ng_zone->add_shipping_method( 'free_shipping' );
		update_option(
			"woocommerce_free_shipping_{$skirttique_instance}_settings",
			array(
				'title'            => 'Delivery on the house',
				'requires'         => 'min_amount',
				'min_amount'       => '150000',
				'ignore_discounts' => 'no',
			)
		);
		WP_CLI::success( "NG zone: free shipping over ₦150,000 (placeholder threshold, instance {$skirttique_instance})." );
	}
}

/* ------------------------------------------------------------------ */
/* Cross-sells on the demo pairs (cart cross-sells + PDP 'Worn with'). */
/* ------------------------------------------------------------------ */
$skirttique_pairs = array(
	'Chidera A-Line Midi'  => array( 'Adaeze Silk Maxi', 'Halima Bias Maxi' ),
	'Yewande Atelier Maxi' => array( 'Folake Wrap Midi', 'Zainab Pleat Midi' ),
	'Halima Bias Maxi'     => array( 'Chidera A-Line Midi', 'Folake Wrap Midi' ),
);

$skirttique_by_name = function ( $name ) {
	$found = wc_get_products( array( 'name' => $name, 'limit' => 1 ) );
	return $found[0] ?? null;
};

foreach ( $skirttique_pairs as $skirttique_owner_name => $skirttique_partner_names ) {
	$skirttique_owner = $skirttique_by_name( $skirttique_owner_name );
	if ( ! $skirttique_owner ) {
		WP_CLI::warning( "No product '{$skirttique_owner_name}' — skipped." );
		continue;
	}
	if ( $skirttique_owner->get_cross_sell_ids() ) {
		WP_CLI::log( "{$skirttique_owner_name}: cross-sells already set — kept." );
		continue;
	}

	$skirttique_ids = array();
	foreach ( $skirttique_partner_names as $skirttique_partner_name ) {
		$skirttique_partner = $skirttique_by_name( $skirttique_partner_name );
		if ( $skirttique_partner ) {
			$skirttique_ids[] = $skirttique_partner->get_id();
		}
	}

	if ( $skirttique_ids ) {
		$skirttique_owner->set_cross_sell_ids( $skirttique_ids );
		$skirttique_owner->save();
		WP_CLI::success( "{$skirttique_owner_name}: cross-sells set (" . implode( ',', $skirttique_ids ) . ').' );
	}
}

/* ------------------------------------------------------------------ */
/* Custom Orders page.                                                 */
/* ------------------------------------------------------------------ */
$skirttique_page = get_page_by_path( 'custom-orders' );

if ( ! $skirttique_page ) {
	$skirttique_page_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'Custom Orders',
			'post_name'   => 'custom-orders',
			'post_status' => 'publish',
		),
		true
	);
	$skirttique_page    = is_wp_error( $skirttique_page_id ) ? null : get_post( $skirttique_page_id );
}

if ( $skirttique_page && ! str_contains( (string) $skirttique_page->post_content, 'wp:skirttique/' ) ) {
	// Hero imagery from media that already exists locally.
	$skirttique_house   = (array) get_option( 'skirttique_house', array() );
	$skirttique_hero_id = absint( $skirttique_house['hero_image_id'] ?? 0 );

	$skirttique_content = sprintf(
		'<!-- wp:skirttique/hero {"eyebrow":"Custom orders","statement":"Cut to your measure","sub":"One client, one pattern — a skirt that exists nowhere else, made in the Lagos atelier.","imageId":%d} /-->

<!-- wp:skirttique/feature-list {"eyebrow":"How it works","title":"Four steps, no mystery","items":"The conversation|Tell the atelier what you have in mind below — a reply comes within two working days.\nThe design|Sketches, cloth options and a firm price, agreed before anything is cut.\nThe making|Eight weeks in the Lagos atelier, with fittings where distance allows.\nThe delivery|Finished, pressed and dispatched — anywhere the house ships."} /-->

<!-- wp:skirttique/pricing {"eyebrow":"Where it starts","title":"Two ways in","items":"The Adjustment|From ₦25,000|An existing silhouette, fitted to your measurements.\nThe Commission|From ₦120,000|A piece designed with you, from fabric to finish."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Before you ask","title":"The usual questions","items":"Can I supply my own fabric?|Yes — the atelier assesses it first for drape and recovery; not every cloth can hold our silhouettes.\nAre bespoke pieces returnable?|A commissioned piece is made for one body, so it returns only if faulty — and the atelier will make it right."} /-->',
		$skirttique_hero_id
	);

	// wp_update_post() unslashes: without wp_slash() the \n escapes in
	// block JSON collapse to bare 'n' and pipe-line lists lose all but
	// their first item.
	wp_update_post(
		array(
			'ID'           => $skirttique_page->ID,
			'post_content' => wp_slash( $skirttique_content ),
		)
	);
	WP_CLI::success( "Custom Orders page #{$skirttique_page->ID} composed from blocks." );
} elseif ( $skirttique_page ) {
	WP_CLI::log( 'Custom Orders page already block-composed — kept.' );
}
