<?php
/**
 * Title: Homepage
 * Slug: skirttique/homepage
 * Categories: skirttique
 * Block Types: core/template-part/homepage
 * Inserter: no
 *
 * The front page: a full-bleed hero, the four real collections, a
 * philosophy statement, a curated product edit (reusing the Stage 6
 * card component), and a quiet closing band that hands off to the
 * footer's house-list form (no second email capture on the page).
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

// Curated, verified editorial photography (Skirttique Design System v1.0,
// §Imagery) — one image per real product_cat term. Replace with brand
// photography as it becomes available; slugs are stable, images are not.
$st_collection_images = array(
	'maxi'            => 'photo-1762342676026-09e25daaf607',
	'midi'            => 'photo-1722486245824-7bb0ff9827dc',
	'limited-edition' => 'photo-1688582949975-98a0750d7505',
	'bespoke'         => 'photo-1604648145659-29f52af5cf7f',
);

$st_categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
	)
);
if ( is_wp_error( $st_categories ) ) {
	$st_categories = array();
}

$st_edit = function_exists( 'wc_get_products' )
	? wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 4,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	)
	: array();

/**
 * Cycle a 1-based stagger index (1-4) for drape delay modifiers.
 */
$st_delay = static fn ( int $i ): string => 'st-drape--delay-' . ( ( $i % 4 ) + 1 );
?>

<main id="main-content">

<section class="st-hero">
	<div class="st-hero__media">
		<img src="https://images.unsplash.com/photo-1693259317871-6dfeb116c763?q=80&amp;w=1800&amp;auto=format&amp;fit=crop" alt="" loading="eager" fetchpriority="high" width="1800" height="1200">
	</div>
	<div class="st-drape">
		<div class="st-hero__content">
			<p class="st-hero__eyebrow"><?php esc_html_e( 'Collection I', 'skirttique' ); ?></p>
			<h1 class="st-hero__statement"><?php esc_html_e( 'The skirt, reconsidered', 'skirttique' ); ?></h1>
			<p class="st-hero__sub"><?php esc_html_e( 'Midi and maxi silhouettes cut for movement, made to be kept.', 'skirttique' ); ?></p>
			<a class="st-hemline st-hero__cta" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'Shop the collection', 'skirttique' ); ?></a>
		</div>
	</div>
</section>

<?php if ( $st_categories ) : ?>
<section class="st-section st-collections" aria-labelledby="st-collections-title">
	<div class="st-drape">
		<div class="st-section__head">
			<p class="st-section__eyebrow"><?php esc_html_e( 'The collections', 'skirttique' ); ?></p>
			<h2 class="st-section__title" id="st-collections-title"><?php esc_html_e( 'Four ways to wear the house', 'skirttique' ); ?></h2>
		</div>
	</div>
	<div class="st-collections__grid">
		<?php foreach ( $st_categories as $st_i => $st_category ) : ?>
			<div class="st-drape <?php echo esc_attr( $st_delay( $st_i ) ); ?>">
				<a class="st-collections__card" href="<?php echo esc_url( get_term_link( $st_category ) ); ?>">
					<span class="st-collections__frame">
						<?php if ( isset( $st_collection_images[ $st_category->slug ] ) ) : ?>
							<img src="https://images.unsplash.com/<?php echo esc_attr( $st_collection_images[ $st_category->slug ] ); ?>?q=80&amp;w=900&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="900" height="1125">
						<?php endif; ?>
					</span>
					<span class="st-collections__name"><?php echo esc_html( $st_category->name ); ?></span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="st-section st-philosophy" aria-labelledby="st-philosophy-title">
	<div class="st-drape">
		<div class="st-philosophy__copy">
			<p class="st-section__eyebrow"><?php esc_html_e( 'The house view', 'skirttique' ); ?></p>
			<h2 class="st-philosophy__statement" id="st-philosophy-title"><?php esc_html_e( 'A skirt is not an afterthought to an outfit. It is the architecture of one.', 'skirttique' ); ?></h2>
			<p class="st-philosophy__prose"><?php esc_html_e( 'Skirttique exists for women who dress with intention — cut for the boardroom and the aisle, the flight and the function, in fabrics chosen to move the way you do. Every piece is made in limited runs, then retired.', 'skirttique' ); ?></p>
			<a class="st-hemline" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'The house, in full', 'skirttique' ); ?></a>
		</div>
	</div>
	<div class="st-drape st-drape--delay-2">
		<figure class="st-philosophy__figure">
			<img src="https://images.unsplash.com/photo-1774380255851-72e8a36d7a59?q=80&amp;w=1200&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="1200" height="1500">
		</figure>
	</div>
</section>

<?php if ( $st_edit ) : ?>
<section class="st-section st-edit" aria-labelledby="st-edit-title">
	<div class="st-drape">
		<div class="st-section__head">
			<p class="st-section__eyebrow"><?php esc_html_e( 'New in', 'skirttique' ); ?></p>
			<h2 class="st-section__title" id="st-edit-title"><?php esc_html_e( 'The current edit', 'skirttique' ); ?></h2>
		</div>
	</div>
	<div class="st-edit__grid">
		<?php
		foreach ( $st_edit as $st_i => $st_product ) {
			if ( ! function_exists( 'Skirttique\WooCommerce\product_card' ) ) {
				break;
			}
			echo '<div class="st-drape ' . esc_attr( $st_delay( $st_i ) ) . '">';
			echo Skirttique\WooCommerce\product_card( $st_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within product_card().
			echo '</div>';
		}
		?>
	</div>
	<div class="st-drape">
		<div class="st-edit__more">
			<a class="st-hemline" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'View everything', 'skirttique' ); ?></a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
/**
 * Filter the homepage voice quotes (placeholder client-voice copy until
 * real press and reviews exist — deliberately no real publication names,
 * we never fabricate an endorsement). Each item: quote + source.
 *
 * @param list<array{quote: string, source: string}> $quotes Quotes, rotated in order.
 */
$st_quotes = apply_filters(
	'skirttique_press_quotes',
	array(
		array(
			'quote'  => __( 'The Halima maxi went from a Lagos boardroom to a London wedding in one suitcase.', 'skirttique' ),
			'source' => __( 'A client, Lagos', 'skirttique' ),
		),
		array(
			'quote'  => __( 'Finally — a house that treats the skirt as the main event, not the afterthought.', 'skirttique' ),
			'source' => __( 'A client, Dubai', 'skirttique' ),
		),
		array(
			'quote'  => __( 'Three seasons in and the pleats still fall exactly where they did on day one.', 'skirttique' ),
			'source' => __( 'A client, New York', 'skirttique' ),
		),
	)
);
?>
<?php if ( $st_quotes ) : ?>
<section class="st-press" aria-label="<?php esc_attr_e( 'In their words', 'skirttique' ); ?>">
	<div class="st-press__inner" data-st-rotate="9000">
		<p class="st-section__eyebrow"><?php esc_html_e( 'In their words', 'skirttique' ); ?></p>
		<ul class="st-press__list">
			<?php foreach ( $st_quotes as $st_i => $st_quote ) : ?>
				<li class="st-press__item<?php echo 0 === $st_i ? ' is-current' : ''; ?>" data-st-rotate-item>
					<blockquote class="st-press__blockquote">
						<p class="st-press__quote"><?php echo esc_html( $st_quote['quote'] ); ?></p>
						<cite class="st-press__source"><?php echo esc_html( $st_quote['source'] ); ?></cite>
					</blockquote>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<section class="st-section st-closing has-foliage-background-color has-nectar-color has-text-color has-background">
	<div class="st-drape">
		<div class="st-closing__inner">
			<h2 class="st-closing__statement"><?php esc_html_e( 'Worn in five countries. Cut in one house.', 'skirttique' ); ?></h2>
			<a class="st-hemline" href="#st-house-list"><?php esc_html_e( 'Join the house list', 'skirttique' ); ?></a>
		</div>
	</div>
</section>

</main>
