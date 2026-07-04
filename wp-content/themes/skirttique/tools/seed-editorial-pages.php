<?php
/**
 * Stage 23: compose the About page from the block library (shipped
 * content, idempotent — skipped once it holds any Skirttique block, so
 * owner edits always win), and — DEV ONLY — add two demo journal
 * articles so the index grid can be seen working.
 *
 *   npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/seed-editorial-pages.php
 *
 * @package Skirttique
 */

// wp eval-file forbids declare(strict_types=1) — do not add it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Editorial imagery pool: the house media that already exists locally
// (Stage 12 sideloads) — never hotlinks.
$skirttique_pool  = array();
$skirttique_house = (array) get_option( 'skirttique_house', array() );
foreach ( array( 'philosophy_image_id', 'hero_image_id' ) as $skirttique_key ) {
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
/* About — the magazine feature (shipped content).                     */
/* ------------------------------------------------------------------ */
$skirttique_about = get_page_by_path( 'about' );

if ( ! $skirttique_about ) {
	$skirttique_about_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'About',
			'post_name'   => 'about',
			'post_status' => 'publish',
		),
		true
	);
	$skirttique_about    = is_wp_error( $skirttique_about_id ) ? null : get_post( $skirttique_about_id );
}

if ( $skirttique_about && ! str_contains( (string) $skirttique_about->post_content, 'wp:skirttique/' ) ) {
	$skirttique_about_content = sprintf(
		'<!-- wp:skirttique/hero {"eyebrow":"About Skirttique","statement":"The house of the skirt","sub":"One garment, taken seriously — designed, cut and finished in Lagos, worn everywhere.","imageId":%1$d} /-->

<!-- wp:skirttique/editorial {"eyebrow":"The beginning","statement":"It began with skirts that were always an afterthought.","prose":"Racks of them — cut fast, sized for no one, gone by the next season. Skirttique set out to do the opposite: one garment, made properly. Midi and maxi silhouettes drafted around stride and seat, in cloths chosen for how they move, made in limited runs and then retired.","imageId":%2$d,"mediaSide":"right"} /-->

<!-- wp:skirttique/stats {"eyebrow":"The house in numbers","items":"5|Markets served\n2|Lengths, perfected\n14|Days to change your mind\n1|Atelier, in Lagos"} /-->

<!-- wp:skirttique/editorial {"eyebrow":"The making","statement":"Cut slowly, finished by hand","prose":"Every piece passes through one atelier — pattern, cloth, pleat and hem — under hands that have cut skirts for decades. Nothing leaves the house until it hangs the way it was drawn.","imageId":%3$d,"mediaSide":"left"} /-->

<!-- wp:skirttique/feature-list {"eyebrow":"What we hold","title":"The convictions","items":"Fabric first|Cloth is chosen for how it moves, not how it photographs.\nLimited runs|Each silhouette is cut in a numbered run, then retired.\nCut for real life|Boardroom to wedding, tarmac to terrace — the same skirt.\nMade to be kept|Built for years of wear, with care notes for every cloth."} /-->

<!-- wp:skirttique/cta {"statement":"See what the house is making now.","ctaLabel":"Shop the collection","ctaUrl":"/shop/"} /-->',
		$skirttique_pick( 0 ),
		$skirttique_pick( 1 ),
		$skirttique_pick( 2 )
	);

	// wp_update_post() unslashes: wp_slash() keeps the \n escapes in the
	// block JSON intact (bare 'n' otherwise — lists collapse to one item).
	wp_update_post(
		array(
			'ID'           => $skirttique_about->ID,
			'post_content' => wp_slash( $skirttique_about_content ),
		)
	);
	WP_CLI::success( "About page #{$skirttique_about->ID} composed from blocks." );
} elseif ( $skirttique_about ) {
	WP_CLI::log( 'About page already block-composed — kept.' );
}

/* ------------------------------------------------------------------ */
/* Demo journal articles — DEV ONLY.                                   */
/* ------------------------------------------------------------------ */
if ( 'production' === wp_get_environment_type() ) {
	WP_CLI::log( 'Production environment — demo journal articles skipped.' );
	return;
}

$skirttique_articles = array(
	array(
		'title'    => 'The Working Wardrobe: Five Days, One Skirt',
		'category' => 'styling',
		'excerpt'  => 'A midi that answers Monday through Friday — how the house styles one piece five ways.',
		'content'  => "<p>The premise is simple: one skirt, five days, no repeats that read as repeats. A midi in a fabric with recovery is the working wardrobe's quiet engine — it holds a press through a commute and a boardroom, then loosens into the evening without asking anything of you.</p><p>Monday wants structure: a tucked shirt, a hard shoe. By Wednesday the same skirt takes a knit and flats and reads entirely differently. Friday, roll the waistband once and let the hem move.</p><p>The point is not versatility for its own sake. It is that a piece cut properly does not need a costume change to change register.</p>",
	),
	array(
		'title'    => 'Pleats, and How to Keep Them',
		'category' => 'care-tips',
		'excerpt'  => 'The pleat is a promise. Here is how to hold it to it.',
		'content'  => "<p>A pleat is pressed memory — and like any memory, it fades fastest when handled carelessly. Three habits keep it sharp.</p><p>First: hang from the waistband, never folded over a bar. The weight of the skirt is what re-draws the pleat every night.</p><p>Second: steam, don't iron. An iron flattens the fold's edge; steam relaxes the cloth around it and lets the pleat re-set itself.</p><p>Third: rest it. A pleated piece worn two days running never gets to recover. Alternate, and the pleats will outlast the occasion you bought them for.</p>",
	),
);

$skirttique_i = 0;
foreach ( $skirttique_articles as $skirttique_article ) {
	if ( get_page_by_path( sanitize_title( $skirttique_article['title'] ), OBJECT, 'post' ) ) {
		WP_CLI::log( "'{$skirttique_article['title']}' exists — kept." );
		++$skirttique_i;
		continue;
	}

	$skirttique_cat  = get_term_by( 'slug', $skirttique_article['category'], 'category' );
	$skirttique_post = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_title'    => $skirttique_article['title'],
			'post_status'   => 'publish',
			'post_excerpt'  => $skirttique_article['excerpt'],
			'post_content'  => $skirttique_article['content'],
			'post_category' => $skirttique_cat ? array( $skirttique_cat->term_id ) : array(),
		),
		true
	);

	if ( ! is_wp_error( $skirttique_post ) ) {
		$skirttique_thumb = $skirttique_pick( $skirttique_i + 3 );
		if ( $skirttique_thumb ) {
			set_post_thumbnail( $skirttique_post, $skirttique_thumb );
		}
		WP_CLI::success( "Journal article #{$skirttique_post}: {$skirttique_article['title']}." );
	}
	++$skirttique_i;
}

// The Stage 18 sample article should sit properly in the grid too.
$skirttique_first = get_page_by_path( 'caring-for-silk', OBJECT, 'post' );
if ( $skirttique_first && ! has_post_thumbnail( $skirttique_first ) && $skirttique_pool ) {
	set_post_thumbnail( $skirttique_first, $skirttique_pick( 5 ) );
	WP_CLI::log( 'Caring for Silk: cover image set.' );
}
