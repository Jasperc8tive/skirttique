<?php
/**
 * Title: Lost (404)
 * Slug: skirttique/lost
 * Categories: skirttique
 * Inserter: no
 *
 * The 404 body (Stage 25): the house's apology, a search for the piece
 * that was wanted, the paths onward, and the collections to fall back
 * into. Everything degrades: the search is a plain GET form.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<section class="st-section st-lost">
	<div class="st-drape"><div>
		<p class="st-section__eyebrow"><?php esc_html_e( 'Page not found', 'skirttique' ); ?></p>
		<h1 class="st-lost__statement"><?php esc_html_e( 'The seam slipped.', 'skirttique' ); ?></h1>
		<p class="st-lost__sub"><?php esc_html_e( 'The page you were after has moved, or never made it off the pattern table. The collection, however, is exactly where it should be.', 'skirttique' ); ?></p>
	</div></div>

	<form class="st-lost__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="st-lost-search"><?php esc_html_e( 'Search the collection', 'skirttique' ); ?></label>
		<input type="search" name="s" id="st-lost-search" placeholder="<?php esc_attr_e( 'Search the collection…', 'skirttique' ); ?>">
		<input type="hidden" name="post_type" value="product">
		<button type="submit" class="st-btn st-btn--primary"><?php esc_html_e( 'Search', 'skirttique' ); ?></button>
	</form>

	<ul class="st-lost__paths">
		<li><a class="st-hemline" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'Shop everything', 'skirttique' ); ?></a></li>
		<li><a class="st-hemline" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'The journal', 'skirttique' ); ?></a></li>
		<li><a class="st-hemline" href="<?php echo esc_url( home_url( '/custom-orders/' ) ); ?>"><?php esc_html_e( 'Custom orders', 'skirttique' ); ?></a></li>
		<li><a class="st-hemline" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'skirttique' ); ?></a></li>
	</ul>
</section>

<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the components.
echo Skirttique\Components\collection_cards(
	array(
		'eyebrow' => __( 'Find your way back', 'skirttique' ),
		'title'   => __( 'The collections', 'skirttique' ),
	)
);

echo Skirttique\Components\product_grid(
	array(
		'eyebrow'    => __( 'Meanwhile, in the atelier', 'skirttique' ),
		'title'      => __( 'The newest pieces', 'skirttique' ),
		'products'   => Skirttique\Components\products( 'newest', 4 ),
		'more_label' => __( 'View everything', 'skirttique' ),
		'more_url'   => (string) $st_shop_url,
	)
);
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
