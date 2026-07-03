<?php
/**
 * Title: Product Page
 * Slug: skirttique/product
 * Categories: skirttique
 * Inserter: no
 *
 * The PDP: stacked editorial gallery on the left, sticky summary on the
 * right (kicker, name, shared purchase form, accordion panels), then
 * "More from the house" (upsells padded with related) and the
 * client-side recently-viewed rail.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_queried_object_id() ) : false;

if ( ! $st_product instanceof WC_Product ) {
	return;
}

// Feed Woo's structured-data collector; it prints JSON-LD at wp_footer.
if ( function_exists( 'WC' ) && WC()->structured_data ) {
	WC()->structured_data->generate_product_data( $st_product );
}

$st_shop_url = wc_get_page_permalink( 'shop' );

$st_kicker   = '';
$st_cat_link = '';
$st_terms    = get_the_terms( $st_product->get_id(), 'product_cat' );
if ( $st_terms && ! is_wp_error( $st_terms ) ) {
	$st_kicker   = $st_terms[0]->name;
	$st_cat_link = (string) get_term_link( $st_terms[0] );
}

$st_gallery_ids = array_merge(
	array( (int) $st_product->get_image_id() ),
	array_map( 'intval', $st_product->get_gallery_image_ids() )
);
$st_gallery_ids = array_values( array_filter( array_unique( $st_gallery_ids ) ) );

$st_companions = Skirttique\WooCommerce\companion_products( $st_product );
?>

<main id="main-content" class="st-pdp" data-st-viewed="<?php echo esc_attr( (string) $st_product->get_id() ); ?>">

	<nav class="st-pdp__crumbs" aria-label="<?php esc_attr_e( 'You are here', 'skirttique' ); ?>">
		<a href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'Shop', 'skirttique' ); ?></a>
		<?php if ( $st_kicker && $st_cat_link ) : ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( $st_cat_link ); ?>"><?php echo esc_html( $st_kicker ); ?></a>
		<?php endif; ?>
		<span aria-hidden="true">/</span>
		<span aria-current="page"><?php echo esc_html( $st_product->get_name() ); ?></span>
	</nav>

	<div class="st-pdp__layout">
		<div class="st-pdp__gallery">
			<?php foreach ( $st_gallery_ids as $st_i => $st_image_id ) : ?>
				<figure class="st-pdp__figure" data-st-zoom>
					<?php
					echo wp_get_attachment_image(
						$st_image_id,
						'woocommerce_single',
						false,
						array(
							'loading'       => 0 === $st_i ? 'eager' : 'lazy',
							'fetchpriority' => 0 === $st_i ? 'high' : 'auto',
							'sizes'         => '(max-width: 60rem) 100vw, 50vw',
						)
					);
					?>
				</figure>
			<?php endforeach; ?>
		</div>

		<div class="st-pdp__summary">
			<?php if ( $st_kicker ) : ?>
				<p class="st-pdp__kicker"><?php echo esc_html( $st_kicker ); ?></p>
			<?php endif; ?>

			<h1 class="st-pdp__name"><?php echo esc_html( $st_product->get_name() ); ?></h1>

			<?php if ( $st_product->get_short_description() ) : ?>
				<div class="st-pdp__lede"><?php echo wp_kses_post( wpautop( $st_product->get_short_description() ) ); ?></div>
			<?php endif; ?>

			<?php echo Skirttique\WooCommerce\purchase_form( $st_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in purchase_form(). ?>

			<?php echo Skirttique\Components\trust_badges( array( 'badges' => array( 'delivery', 'returns', 'secure' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component. ?>

			<?php echo Skirttique\WooCommerce\product_pairing( $st_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_pairing(). ?>

			<div class="st-pdp__panels">
				<?php foreach ( Skirttique\WooCommerce\pdp_panels( $st_product ) as $st_panel ) : ?>
					<details class="st-panel-fold">
						<summary><?php echo esc_html( $st_panel['title'] ); ?></summary>
						<div class="st-panel-fold__body"><?php echo $st_panel['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() in pdp_panels(). ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php if ( $st_companions ) : ?>
		<section class="st-pdp__rail" aria-labelledby="st-companions-title">
			<div class="st-section__head">
				<p class="st-section__eyebrow"><?php esc_html_e( 'Complete the look', 'skirttique' ); ?></p>
				<h2 class="st-section__title st-pdp__rail-title" id="st-companions-title"><?php esc_html_e( 'More from the house', 'skirttique' ); ?></h2>
			</div>
			<div class="st-card-grid">
				<?php
				foreach ( $st_companions as $st_companion ) {
					echo Skirttique\WooCommerce\product_card( $st_companion ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_card().
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php echo Skirttique\WooCommerce\product_reviews( $st_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_reviews(). ?>

	<section class="st-pdp__rail" data-st-recent hidden aria-labelledby="st-recent-title">
		<div class="st-section__head">
			<p class="st-section__eyebrow"><?php esc_html_e( 'Where you have been', 'skirttique' ); ?></p>
			<h2 class="st-section__title st-pdp__rail-title" id="st-recent-title"><?php esc_html_e( 'Recently viewed', 'skirttique' ); ?></h2>
		</div>
		<div class="st-card-grid" data-st-recent-grid></div>
	</section>

	<?php if ( $st_product->is_purchasable() && $st_product->is_in_stock() ) : ?>
		<div class="st-sticky-buy" data-st-sticky-buy hidden>
			<div class="st-sticky-buy__meta">
				<span class="st-sticky-buy__name"><?php echo esc_html( $st_product->get_name() ); ?></span>
				<span class="st-sticky-buy__price"><?php echo $st_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML. ?></span>
			</div>
			<button type="button" class="st-btn st-btn--primary st-sticky-buy__cta" data-st-sticky-cta>
				<?php echo esc_html( $st_product->is_type( 'variable' ) ? __( 'Choose size', 'skirttique' ) : __( 'Add to bag', 'skirttique' ) ); ?>
			</button>
		</div>
	<?php endif; ?>

</main>
