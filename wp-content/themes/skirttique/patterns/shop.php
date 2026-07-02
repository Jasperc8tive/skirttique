<?php
/**
 * Title: Shop
 * Slug: skirttique/shop
 * Categories: skirttique
 * Block Types: core/query
 * Inserter: no
 *
 * The catalog: editorial collection header, collection switcher, toolbar
 * (count + sort), the product-card grid, and pagination. Serves the shop
 * page, every collection (product_cat) archive, and product search —
 * WooCommerce's template hierarchy funnels them all into
 * templates/archive-product.html, which renders this pattern inside the
 * main product query.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	return;
}

global $wp_query;

$st_shop_url = wc_get_page_permalink( 'shop' );

// ---------------------------------------------------------------------
// Page heading: collection name, search phrase, or the whole house.
// ---------------------------------------------------------------------
$st_heading     = __( 'Everything', 'skirttique' );
$st_description = __( 'Midi and maxi skirts, made to be kept.', 'skirttique' );
$st_current_cat = 0;

if ( is_product_category() ) {
	$st_term        = get_queried_object();
	$st_heading     = $st_term->name;
	$st_description = $st_term->description;
	$st_current_cat = (int) $st_term->term_id;
} elseif ( is_search() ) {
	/* translators: %s: search query. */
	$st_heading     = sprintf( __( 'Search — %s', 'skirttique' ), get_search_query() );
	$st_description = '';
}

$st_categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'name',
		'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
	)
);
if ( is_wp_error( $st_categories ) ) {
	$st_categories = array();
}

$st_total   = (int) $wp_query->found_posts;
$st_orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'date'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort choice.
?>

<div class="st-shop">
	<header class="st-shop__head">
		<p class="st-shop__eyebrow"><?php esc_html_e( 'The collection', 'skirttique' ); ?></p>
		<h1 class="st-shop__title"><?php echo esc_html( $st_heading ); ?></h1>
		<?php if ( $st_description ) : ?>
			<p class="st-shop__lede"><?php echo esc_html( $st_description ); ?></p>
		<?php endif; ?>

		<?php if ( $st_categories ) : ?>
			<nav class="st-shop__collections" aria-label="<?php esc_attr_e( 'Collections', 'skirttique' ); ?>">
				<ul>
					<li><a href="<?php echo esc_url( $st_shop_url ); ?>" <?php echo ( ! $st_current_cat && ! is_search() ) ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'Everything', 'skirttique' ); ?></a></li>
					<?php foreach ( $st_categories as $st_category ) : ?>
						<li>
							<a href="<?php echo esc_url( get_term_link( $st_category ) ); ?>" <?php echo $st_category->term_id === $st_current_cat ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $st_category->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="st-shop__toolbar">
			<p class="st-shop__count" role="status">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of products. */
						_n( '%d piece', '%d pieces', $st_total, 'skirttique' ),
						$st_total
					)
				);
				?>
			</p>

			<form class="st-shop__sort" method="get">
				<label for="st-orderby"><?php esc_html_e( 'Sort', 'skirttique' ); ?></label>
				<select id="st-orderby" name="orderby" data-st-auto-submit>
					<?php foreach ( \Skirttique\WooCommerce\catalog_orderings() as $st_value => $st_label ) : ?>
						<option value="<?php echo esc_attr( $st_value ); ?>" <?php selected( $st_orderby, $st_value ); ?>><?php echo esc_html( $st_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( is_search() ) : ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
				<noscript><button type="submit" class="st-btn st-btn--secondary"><?php esc_html_e( 'Apply', 'skirttique' ); ?></button></noscript>
			</form>
		</div>

		<div class="st-shop__grid">
			<?php
			while ( have_posts() ) {
				the_post();
				global $product;
				if ( $product instanceof \WC_Product ) {
					echo \Skirttique\WooCommerce\product_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_card().
				}
			}
			?>
		</div>

		<?php
		$st_pages = paginate_links(
			array(
				'type'      => 'list',
				'prev_text' => __( 'Previous', 'skirttique' ),
				'next_text' => __( 'Next', 'skirttique' ),
			)
		);
		if ( $st_pages ) :
			?>
			<nav class="st-shop__pages" aria-label="<?php esc_attr_e( 'Catalog pages', 'skirttique' ); ?>">
				<?php echo $st_pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core paginate_links(). ?>
			</nav>
		<?php endif; ?>

	<?php else : ?>
		<div class="st-shop__empty">
			<p class="st-shop__empty-note"><?php esc_html_e( 'Nothing here yet.', 'skirttique' ); ?></p>
			<a class="st-hemline" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'View everything', 'skirttique' ); ?></a>
		</div>
	<?php endif; ?>
</div>
