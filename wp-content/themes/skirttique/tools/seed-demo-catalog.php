<?php
/**
 * Dev-only demo catalog seed (Stage 6).
 *
 * Run inside wp-env:
 *   npx wp-env run cli -- wp eval-file wp-content/themes/skirttique/tools/seed-demo-catalog.php
 *
 * Localises the store (NGN, Lagos), then creates eight placeholder skirts
 * across the four collections using the verified Unsplash editorial images.
 * Idempotent: products are matched by name and skipped when present.
 * Placeholder content only — the content stage replaces all of it.
 *
 * @package Skirttique
 */

// No strict_types: wp eval-file evals the file body, where declare() is illegal.

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_products' ) ) {
	echo "WooCommerce not loaded — aborting.\n";
	return;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// ---------------------------------------------------------------------
// Store localisation — NGN base currency, ₦12,500 format (no kobo).
// ---------------------------------------------------------------------
update_option( 'woocommerce_currency', 'NGN' );
update_option( 'woocommerce_currency_pos', 'left' );
update_option( 'woocommerce_price_thousand_sep', ',' );
update_option( 'woocommerce_price_decimal_sep', '.' );
update_option( 'woocommerce_price_num_decimals', '0' );
update_option( 'woocommerce_default_country', 'NG:LA' );
echo "Store localised: NGN, Lagos.\n";

// ---------------------------------------------------------------------
// Images — the four verified Unsplash editorial shots, two crops each.
// ---------------------------------------------------------------------
$st_bases = array(
	'A' => 'photo-1688582949975-98a0750d7505',
	'B' => 'photo-1604648145659-29f52af5cf7f',
	'C' => 'photo-1762342676026-09e25daaf607',
	'D' => 'photo-1722486245824-7bb0ff9827dc',
);

$st_image_ids = array();
foreach ( $st_bases as $st_key => $st_base ) {
	foreach ( array( 1 => 'crop=entropy', 2 => 'crop=faces' ) as $st_crop_n => $st_crop ) {
		$st_url   = "https://images.unsplash.com/{$st_base}?q=80&w=900&h=1200&fit=crop&auto=format&{$st_crop}";
		$st_label = "skirttique-demo-{$st_key}{$st_crop_n}";

		$st_existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'title'       => $st_label,
				'fields'      => 'ids',
				'numberposts' => 1,
			)
		);
		if ( $st_existing ) {
			$st_image_ids[ $st_key . $st_crop_n ] = (int) $st_existing[0];
			continue;
		}

		// download_url + media_handle_sideload: media_sideload_image()
		// rejects Unsplash URLs (no file extension in the path).
		$st_tmp = download_url( $st_url );
		if ( is_wp_error( $st_tmp ) ) {
			echo "image {$st_key}{$st_crop_n} failed: " . $st_tmp->get_error_message() . "\n";
			continue;
		}
		$st_id = media_handle_sideload(
			array(
				'name'     => "{$st_label}.jpg",
				'tmp_name' => $st_tmp,
			),
			0,
			$st_label
		);
		if ( is_wp_error( $st_id ) ) {
			@unlink( $st_tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			echo "image {$st_key}{$st_crop_n} failed: " . $st_id->get_error_message() . "\n";
			continue;
		}
		wp_update_post(
			array(
				'ID'         => $st_id,
				'post_title' => $st_label,
			)
		);
		$st_image_ids[ $st_key . $st_crop_n ] = (int) $st_id;
		echo "image {$st_key}{$st_crop_n} → #{$st_id}\n";
	}
}

// ---------------------------------------------------------------------
// Size attribute (for the one variable product).
// ---------------------------------------------------------------------
if ( ! taxonomy_exists( 'pa_size' ) ) {
	$st_attr_id = wc_create_attribute(
		array(
			'name' => 'Size',
			'slug' => 'size',
			'type' => 'select',
		)
	);
	if ( is_wp_error( $st_attr_id ) ) {
		echo 'size attribute failed: ' . $st_attr_id->get_error_message() . "\n";
	} else {
		register_taxonomy( 'pa_size', 'product' ); // Available in this request.
	}
}
foreach ( array( 'S', 'M', 'L' ) as $st_size ) {
	if ( taxonomy_exists( 'pa_size' ) && ! term_exists( $st_size, 'pa_size' ) ) {
		wp_insert_term( $st_size, 'pa_size' );
	}
}

// ---------------------------------------------------------------------
// Products.
// ---------------------------------------------------------------------
$st_cat_ids = array();
foreach ( array( 'Maxi Skirts', 'Midi Skirts', 'Limited Edition', 'Bespoke' ) as $st_cat_name ) {
	$st_term                      = get_term_by( 'name', $st_cat_name, 'product_cat' );
	$st_cat_ids[ $st_cat_name ] = $st_term ? (int) $st_term->term_id : 0;
}

$st_products = array(
	array( 'Adaeze Silk Maxi', array( 'Maxi Skirts' ), 68500, null, 'A1', 'B1', 'Fluid silk, floor-grazing, cut on the straight grain.' ),
	array( 'Zainab Pleat Midi', array( 'Midi Skirts' ), 54000, null, 'B1', 'C1', 'Knife pleats that move like water, holding their line all day.' ),
	array( 'Amara Column Maxi', array( 'Maxi Skirts', 'Limited Edition' ), 92000, null, 'C1', 'D1', 'A single uninterrupted column, limited to this season.' ),
	array( 'Folake Wrap Midi', array( 'Midi Skirts' ), 49500, 39500, 'D1', 'A1', 'A true wrap with a self-tie waist and a clean drape.' ),
	array( 'Ngozi Tiered Maxi', array( 'Maxi Skirts' ), 71000, null, 'A2', 'C2', 'Three gathered tiers, weighted hem, quiet volume.', 'variable' ),
	array( 'Halima Bias Maxi', array( 'Maxi Skirts', 'Limited Edition' ), 85000, null, 'B2', 'D2', 'Cut on the bias so the fabric falls close, then flares.' ),
	array( 'Chidera A-Line Midi', array( 'Midi Skirts' ), 46000, null, 'C2', 'A2', 'The everyday A-line — structured, softly flared, pocketed.' ),
	array( 'Yewande Atelier Maxi', array( 'Bespoke' ), 120000, null, 'D2', 'B2', 'Made to your measure in the Lagos atelier. Eight-week lead.' ),
);

foreach ( $st_products as $st_row ) {
	list( $st_name, $st_cats, $st_price, $st_sale, $st_img, $st_hover, $st_excerpt ) = $st_row;
	$st_is_variable = 'variable' === ( $st_row[7] ?? '' );

	$st_found = wc_get_products( array( 'name' => $st_name, 'limit' => 1 ) );
	if ( $st_found ) {
		// Backfill images when a previous run created the product bare.
		$st_existing_product = $st_found[0];
		if ( ! $st_existing_product->get_image_id() && isset( $st_image_ids[ $st_img ] ) ) {
			$st_existing_product->set_image_id( $st_image_ids[ $st_img ] );
			if ( isset( $st_image_ids[ $st_hover ] ) ) {
				$st_existing_product->set_gallery_image_ids( array( $st_image_ids[ $st_hover ] ) );
			}
			$st_existing_product->save();
			echo "{$st_name}: images attached.\n";
		} else {
			echo "{$st_name}: exists, skipped.\n";
		}
		continue;
	}

	$st_product = $st_is_variable ? new WC_Product_Variable() : new WC_Product_Simple();
	$st_product->set_name( $st_name );
	$st_product->set_status( 'publish' );
	$st_product->set_short_description( $st_excerpt );
	$st_product->set_category_ids( array_values( array_filter( array_map( fn( $c ) => $st_cat_ids[ $c ] ?? 0, $st_cats ) ) ) );

	if ( isset( $st_image_ids[ $st_img ] ) ) {
		$st_product->set_image_id( $st_image_ids[ $st_img ] );
	}
	if ( isset( $st_image_ids[ $st_hover ] ) ) {
		$st_product->set_gallery_image_ids( array( $st_image_ids[ $st_hover ] ) );
	}

	if ( $st_is_variable && taxonomy_exists( 'pa_size' ) ) {
		$st_attribute = new WC_Product_Attribute();
		$st_attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_size' ) );
		$st_attribute->set_name( 'pa_size' );
		$st_attribute->set_options( array_map( fn( $s ) => (int) get_term_by( 'name', $s, 'pa_size' )->term_id, array( 'S', 'M', 'L' ) ) );
		$st_attribute->set_visible( true );
		$st_attribute->set_variation( true );
		$st_product->set_attributes( array( $st_attribute ) );
		$st_parent_id = $st_product->save();

		foreach ( array( 's', 'm', 'l' ) as $st_slug ) {
			$st_variation = new WC_Product_Variation();
			$st_variation->set_parent_id( $st_parent_id );
			$st_variation->set_attributes( array( 'pa_size' => $st_slug ) );
			$st_variation->set_regular_price( (string) $st_price );
			$st_variation->set_status( 'publish' );
			$st_variation->save();
		}
		WC_Product_Variable::sync( $st_parent_id );
		echo "{$st_name}: created (variable) #{$st_parent_id}\n";
		continue;
	}

	$st_product->set_regular_price( (string) $st_price );
	if ( null !== $st_sale ) {
		$st_product->set_sale_price( (string) $st_sale );
	}
	$st_id = $st_product->save();
	echo "{$st_name}: created #{$st_id}\n";
}

echo "Seed complete.\n";
