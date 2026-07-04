<?php
/**
 * Stage 17: create/update the private "Component Library" page — every
 * Skirttique block instantiated once, as both a rendering test bed and
 * a living reference for editors.
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/seed-component-library.php
 *
 * Idempotent: updates the same page (slug component-library) on re-run.
 * The page is PRIVATE — visible to logged-in editors only, never in
 * sitemaps or menus.
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$skirttique_blocks = <<<'HTML'
<!-- wp:skirttique/hero {"eyebrow":"Component Library","statement":"Every block, once","sub":"The Skirttique block library — the sections every page composes from.","ctaLabel":"Shop the collection","ctaUrl":"/shop/","isBanner":true} /-->

<!-- wp:skirttique/collection-cards /-->

<!-- wp:skirttique/editorial {"eyebrow":"Editorial","statement":"Statement beside a figure.","prose":"The philosophy layout, generalised — any statement, any prose, either side.","ctaLabel":"The house, in full","ctaUrl":"/about/","mediaSide":"left"} /-->

<!-- wp:skirttique/product-grid {"eyebrow":"Product grid","title":"Newest four","count":4,"moreLabel":"View everything","moreUrl":"/shop/"} /-->

<!-- wp:skirttique/product-slider {"eyebrow":"Product slider","title":"The rail","count":8} /-->

<!-- wp:skirttique/stats {"eyebrow":"Statistics","title":"The house in numbers","items":"5|Markets served\n14|Days to return\n1|Skirt at the centre of everything"} /-->

<!-- wp:skirttique/feature-list {"eyebrow":"Feature list","title":"Why Skirttique","items":"Cut for movement|Every silhouette is drafted around stride and seat, not a mannequin.\nLimited runs|Made in small numbers, then retired — never mass produced.\nOne house|Designed, cut, and finished under one roof in Lagos."} /-->

<!-- wp:skirttique/testimonials {"quotes":"The Halima maxi went from a Lagos boardroom to a London wedding in one suitcase.|A client, Lagos\nFinally — a house that treats the skirt as the main event.|A client, Dubai"} /-->

<!-- wp:skirttique/faq {"eyebrow":"FAQ","title":"Questions","items":"Where do you ship from?|Every piece is made and dispatched from Lagos.\nWhat currency will I pay in?|The currency of your chosen market, selected in the header."} /-->

<!-- wp:skirttique/pricing {"eyebrow":"Pricing","title":"Bespoke tiers","items":"The Adjustment|From ₦25,000|An existing silhouette, fitted to your measurements.\nThe Commission|From ₦120,000|A piece designed with you, from fabric to finish."} /-->

<!-- wp:skirttique/trust-badges {"made":true} /-->

<!-- wp:skirttique/featured-collection /-->

<!-- wp:skirttique/featured-product /-->

<!-- wp:skirttique/lookbook /-->

<!-- wp:skirttique/instagram {"url":"https://www.instagram.com/"} /-->

<!-- wp:skirttique/breadcrumbs /-->

<!-- wp:skirttique/newsletter /-->

<!-- wp:skirttique/cta {"statement":"Composed entirely from blocks.","ctaLabel":"Join the house list","ctaUrl":"#st-house-list"} /-->
HTML;

$skirttique_existing = get_page_by_path( 'component-library' );

$skirttique_args = array(
	'post_type'    => 'page',
	'post_title'   => 'Component Library',
	'post_name'    => 'component-library',
	'post_status'  => 'private',
	// wp_slash: insert/update unslash, which would turn the \n escapes
	// in block JSON into bare 'n' and collapse the pipe-line lists.
	'post_content' => wp_slash( $skirttique_blocks ),
);

if ( $skirttique_existing ) {
	$skirttique_args['ID'] = $skirttique_existing->ID;
	$skirttique_id         = wp_update_post( $skirttique_args, true );
} else {
	$skirttique_id = wp_insert_post( $skirttique_args, true );
}

if ( is_wp_error( $skirttique_id ) ) {
	WP_CLI::error( $skirttique_id->get_error_message() );
}

WP_CLI::success( "Component Library page #{$skirttique_id} (private, /component-library/)." );
