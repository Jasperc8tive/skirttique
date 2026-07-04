<?php
/**
 * Stage 25: compose the utility pages from the block library — Size
 * Guide, FAQs, Contact, Newsletter, Visit — and (DEV ONLY) publish a
 * demo campaign so the landing template can be seen working.
 *
 *   npx wp-env run cli wp eval-file --use-include wp-content/themes/skirttique/tools/seed-utility-pages.php
 *
 * --use-include is REQUIRED: plain eval-file rewrites path constants
 * with a regex that exhausts the PCRE JIT stack on this file's long
 * string literals — preg_replace returns null and the whole script
 * silently evaluates to nothing (exit 0, no output, no pages).
 *
 * Idempotent the Stage 23 way: a page is skipped once it holds any
 * Skirttique block, so owner edits always win. Content is wp_slash()ed
 * before insert/update — wp_update_post() unslashes, and the \n escapes
 * inside block JSON must survive (the Stage 24 lesson).
 *
 * (wp eval-file forbids declare(strict_types=1) — do not add it.)
 *
 * @package Skirttique
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Editorial imagery pool: the house media that already exists locally
// (Stage 12 sideloads) — never hotlinks.
$skirttique_pool  = array();
$skirttique_house = (array) get_option( 'skirttique_house', array() );
foreach ( array( 'philosophy_image_id', 'hero_image_id', 'craft_image_id' ) as $skirttique_key ) {
	$skirttique_id = absint( $skirttique_house[ $skirttique_key ] ?? 0 );
	if ( $skirttique_id ) {
		$skirttique_pool[] = $skirttique_id;
	}
}
$skirttique_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
foreach ( is_wp_error( $skirttique_terms ) ? array() : $skirttique_terms as $skirttique_term ) {
	$skirttique_id = absint( get_term_meta( $skirttique_term->term_id, 'thumbnail_id', true ) );
	if ( $skirttique_id ) {
		$skirttique_pool[] = $skirttique_id;
	}
}
$skirttique_pool = array_values( array_unique( $skirttique_pool ) );
$skirttique_pick = function ( $i ) use ( $skirttique_pool ) {
	return $skirttique_pool ? $skirttique_pool[ $i % count( $skirttique_pool ) ] : 0;
};

/* ------------------------------------------------------------------ */
/* The compositions.                                                    */
/* ------------------------------------------------------------------ */

$skirttique_pages = array(

	'size-guide' => array(
		'title'    => 'Size Guide',
		'template' => 'page-canvas',
		'content'  => sprintf(
			'<!-- wp:skirttique/hero {"eyebrow":"The size guide","statement":"Measured for movement","sub":"Five sizes, three numbers each — and an atelier that answers when the tape is undecided.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:skirttique/size-chart {"items":"XS|62|88|97\nS|66|92|98\nM|71|97|99\nL|77|103|100\nXL|84|110|101"} /-->

<!-- wp:skirttique/feature-list {"eyebrow":"Taking the tape","title":"How to measure","items":"Waist|Around the natural crease where you bend, over light clothing — snug, never tight.\nHips|Around the fullest point with your feet together — the tape should slide, not dig.\nLength|From the natural waist to where you want the hem to fall; our lengths are quoted the same way.\nBetween sizes|Take the larger. The waist is where the piece should sit closest, and taking in is simple."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Fit, answered","title":"The common questions","items":"Do the skirts run true to size?|True to the chart above — every piece is cut to these numbers, and the product page notes any silhouette that wears differently.\nWhat if my measurements sit in two sizes?|Take the larger and let the waist be fitted; client care can arrange a complimentary waist adjustment on unworn pieces.\nCan a piece be cut to my exact measurements?|Yes — that is the custom orders service, from made-to-measure adjustments to a fully bespoke piece."} /-->

<!-- wp:skirttique/cta {"statement":"Still between two numbers? Send them to us.","ctaLabel":"Write to client care","ctaUrl":"/contact/"} /-->',
			$skirttique_pick( 0 )
		),
	),

	'faqs' => array(
		'title'    => 'FAQs',
		'template' => 'page-canvas',
		'content'  => sprintf(
			'<!-- wp:skirttique/hero {"eyebrow":"Client care","statement":"Questions, answered","sub":"Ordering, delivery, sizing, the atelier — the short answers, honestly given.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:paragraph {"className":"st-jump"} --><p class="st-jump"><a href="#ordering">Ordering</a><a href="#delivery">Delivery</a><a href="#returns">Returns</a><a href="#sizing">Sizing &amp; fit</a><a href="#bespoke">Custom orders</a><a href="#care">Care</a></p><!-- /wp:paragraph -->

<!-- wp:skirttique/faq {"eyebrow":"Ordering","title":"Placing an order","anchor":"ordering","items":"Which currencies can I pay in?|Prices are shown and charged in your chosen market’s currency — naira at home, with US dollars, pounds, rand and dirhams for the international markets.\nWhich payment methods do you take?|Cards and bank transfer through Paystack in Nigeria; cards and wallets through Stripe internationally. Every payment is encrypted end to end.\nCan I change or cancel an order?|Write to client care quickly — an order can be changed or cancelled any time before it is dispatched.\nDo I need an account to order?|No. An account simply keeps your addresses, orders and saved pieces in one place."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Delivery","title":"Getting it to you","anchor":"delivery","items":"How long does delivery take?|Ready-to-wear pieces leave the atelier within 1–2 business days: Lagos typically sees them in 1–2 days, the rest of Nigeria in 2–5, and international cities in 3–7 by express courier.\nHow much does delivery cost?|The exact cost is shown at checkout before you pay — and NG orders above the delivery-on-the-house threshold ship free.\nWill I be charged import duties?|Orders outside Nigeria may attract duties or taxes set by your country, payable on arrival; they are not included in our prices.\nIs there tracking?|Always. The tracking number lands in your inbox the moment the piece leaves."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Returns","title":"If it is not right","anchor":"returns","items":"What is the return window?|Fourteen days from delivery, unworn, tags attached, in the original wrapping.\nHow do I start a return?|Write to care@skirttique.com with your order number — instructions come back the same business day.\nWhen is the refund paid?|Within 14 days of the piece reaching us and being checked, back to the original payment method.\nCan I exchange for another size?|Yes — write first, and where stock allows we hold the size you need while your return travels."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Sizing & fit","title":"The right size","anchor":"sizing","items":"How do I find my size?|The size guide carries the full chart in centimetres and inches, with how-to-measure notes.\nWhat if I am between sizes?|Take the larger — the waist is where a piece should sit closest, and taking in is simple.\nAre lengths adjustable?|Hem adjustments on unworn pieces can be arranged through client care; bespoke lengths are part of the custom orders service."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Custom orders","title":"The bespoke service","anchor":"bespoke","items":"What can be commissioned?|From a made-to-measure cut of an existing silhouette to a fully bespoke piece designed with the atelier.\nHow long does a commission take?|The timeline is agreed when the design is — most pieces take three to six weeks from agreed design to dispatch.\nIs a deposit required?|No deposit is taken until the design is agreed in writing."} /-->

<!-- wp:skirttique/faq {"eyebrow":"Care","title":"Keeping it beautiful","anchor":"care","items":"How should I care for my piece?|Each piece carries its own care note; the house rule is dry-clean or a cold gentle hand-wash where the label allows, steam rather than iron, and rest between wears.\nHow do I keep pleats sharp?|Hang from the waistband, steam rather than iron, and let the piece rest a day between wears — pressed memory lasts when it is not overworked.\nWhat if a seam or button gives?|Write to client care — small repairs on house pieces are handled by the atelier."} /-->

<!-- wp:skirttique/cta {"statement":"Anything unanswered — ask a person.","ctaLabel":"Contact client care","ctaUrl":"/contact/"} /-->',
			$skirttique_pick( 1 )
		),
	),

	'contact' => array(
		'title'    => 'Contact',
		'template' => '', // page-contact.html applies by slug (it appends the form).
		'content'  => sprintf(
			'<!-- wp:skirttique/hero {"eyebrow":"Contact","statement":"The house is listening","sub":"An order, a piece, an occasion, an idea — every message is read by a person, and answered by one.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:skirttique/contact-details /-->',
			$skirttique_pick( 2 )
		),
	),

	'newsletter' => array(
		'title'    => 'Newsletter',
		'template' => 'page-canvas',
		'content'  => sprintf(
			'<!-- wp:skirttique/hero {"eyebrow":"The house list","statement":"A few letters a season","sub":"The pieces first, the previews privately, the journal when it matters — and nothing else.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:skirttique/feature-list {"eyebrow":"What the letters hold","title":"Why join","items":"First to every collection|New pieces reach the list before they reach the shop.\nPrivate previews|Limited runs are small — the list sees them while every size is still there.\nThe journal|Styling notes and care guides, written to be kept.\nNo noise|A few letters a season. Leaving takes one click, any time."} /-->

<!-- wp:skirttique/newsletter /-->',
			$skirttique_pick( 3 )
		),
	),

	'visit' => array(
		'title'    => 'Visit the Atelier',
		'template' => 'page-canvas',
		'content'  => sprintf(
			'<!-- wp:skirttique/hero {"eyebrow":"Visit","statement":"The atelier receives","sub":"Fittings, commissions, and conversations about cloth — by appointment, in Lagos.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:skirttique/editorial {"eyebrow":"The visit","statement":"An hour with the tape and the cloth","prose":"A visit is unhurried: measurements taken properly, silhouettes tried against your stride, cloths handled rather than described. Come with an occasion or simply with curiosity — the atelier receives by appointment so the hour is yours.","imageId":%2$d,"mediaSide":"right"} /-->

<!-- wp:skirttique/locations /-->

<!-- wp:skirttique/cta {"statement":"Ask for an appointment — the diary is kept by a person.","ctaLabel":"Arrange a visit","ctaUrl":"/contact/"} /-->',
			$skirttique_pick( 4 ),
			$skirttique_pick( 5 )
		),
	),
);

foreach ( $skirttique_pages as $skirttique_slug => $skirttique_page ) {
	$skirttique_existing = get_page_by_path( $skirttique_slug );

	if ( ! $skirttique_existing ) {
		$skirttique_new_id   = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => $skirttique_page['title'],
				'post_name'   => $skirttique_slug,
				'post_status' => 'publish',
			),
			true
		);
		$skirttique_existing = is_wp_error( $skirttique_new_id ) ? null : get_post( $skirttique_new_id );
	}

	if ( ! $skirttique_existing ) {
		WP_CLI::warning( "Could not create page: {$skirttique_slug}." );
		continue;
	}

	if ( str_contains( (string) $skirttique_existing->post_content, 'wp:skirttique/' ) ) {
		WP_CLI::log( "{$skirttique_slug} already block-composed — kept." );
		continue;
	}

	wp_update_post(
		array(
			'ID'           => $skirttique_existing->ID,
			'post_title'   => $skirttique_page['title'],
			'post_content' => wp_slash( $skirttique_page['content'] ),
		)
	);

	if ( '' !== $skirttique_page['template'] ) {
		update_post_meta( $skirttique_existing->ID, '_wp_page_template', $skirttique_page['template'] );
	}

	WP_CLI::success( "{$skirttique_slug} (#{$skirttique_existing->ID}) composed from blocks." );
}

/* ------------------------------------------------------------------ */
/* Demo campaign — DEV ONLY (the landing template, seen working).      */
/* ------------------------------------------------------------------ */
if ( 'production' === wp_get_environment_type() ) {
	WP_CLI::log( 'Production environment — demo campaign skipped.' );
	return;
}

if ( get_page_by_path( 'midnight-in-lagos', OBJECT, 'campaign' ) ) {
	WP_CLI::log( 'Demo campaign exists — kept.' );
	return;
}

$skirttique_campaign = wp_insert_post(
	array(
		'post_type'    => 'campaign',
		'post_title'   => 'Midnight in Lagos',
		'post_name'    => 'midnight-in-lagos',
		'post_status'  => 'publish',
		'post_excerpt' => 'The evening edit — pieces cut for hours that begin after dark.',
		'post_content' => wp_slash(
			sprintf(
				'<!-- wp:skirttique/hero {"eyebrow":"The campaign","statement":"Midnight in Lagos","sub":"The evening edit: hems that answer candlelight, waists that survive the dinner.","imageId":%1$d,"isBanner":false} /-->

<!-- wp:skirttique/editorial {"eyebrow":"The idea","statement":"Evening, without the costume","prose":"An evening skirt should not need managing. These pieces move from the table to the dance floor on the same waistband — cloths with weight enough to fall straight and give enough to be forgotten.","imageId":%2$d,"mediaSide":"left"} /-->

<!-- wp:skirttique/product-grid {"eyebrow":"The edit","title":"The pieces","source":"newest","count":4} /-->

<!-- wp:skirttique/cta {"statement":"The rest of the house is one door away.","ctaLabel":"Shop everything","ctaUrl":"/shop/"} /-->',
				$skirttique_pick( 0 ),
				$skirttique_pick( 3 )
			)
		),
	),
	true
);

if ( is_wp_error( $skirttique_campaign ) ) {
	WP_CLI::warning( 'Demo campaign could not be created: ' . $skirttique_campaign->get_error_message() );
	return;
}

// The decision of record (Stage 18): campaigns launch noindexed; the
// owner lifts it per campaign in Rank Math when a landing should rank.
update_post_meta( $skirttique_campaign, 'rank_math_robots', array( 'noindex' ) );

$skirttique_thumb = $skirttique_pick( 1 );
if ( $skirttique_thumb ) {
	set_post_thumbnail( $skirttique_campaign, $skirttique_thumb );
}

WP_CLI::success( "Demo campaign #{$skirttique_campaign}: Midnight in Lagos (noindexed)." );
