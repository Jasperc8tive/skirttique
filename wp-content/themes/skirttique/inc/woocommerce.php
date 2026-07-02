<?php
/**
 * WooCommerce presentation glue.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\WooCommerce;

/**
 * Current cart item count (0 when WooCommerce or the cart is unavailable).
 */
function cart_count(): int {
	if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Render the header cart-count bubble. Registered as a cart fragment so
 * WooCommerce refreshes it after every AJAX cart change.
 */
function cart_count_bubble(): string {
	$count = cart_count();

	return sprintf(
		'<span class="st-cart-count%s" data-st-cart-count aria-hidden="true">%s</span>',
		0 === $count ? ' is-empty' : '',
		esc_html( (string) $count )
	);
}

/**
 * Register header fragments with WooCommerce's cart-fragments refresh.
 *
 * @param array<string, string> $fragments Fragment selector → markup map.
 * @return array<string, string>
 */
function cart_fragments( array $fragments ): array {
	$fragments['[data-st-cart-count]'] = cart_count_bubble();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\cart_fragments' );

/**
 * Ensure the fragments script runs so the count and drawer stay live.
 */
function enqueue_fragments(): void {
	if ( function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_fragments', 20 );

/**
 * Render a product card (Stage 6, design system §7).
 *
 * 3:4 frame crossfading to the first gallery image on hover; quick-add
 * bar rises on hover and stays visible on keyboard focus; meta is
 * kicker (first category) / serif name / price. Sale price is the
 * view's single amber moment (styled in _card.scss via Woo's <ins>).
 */
function product_card( \WC_Product $product ): string {
	$permalink = $product->get_permalink();
	$name      = $product->get_name();

	$primary = wp_get_attachment_image(
		(int) $product->get_image_id(),
		'woocommerce_thumbnail',
		false,
		array(
			'class'   => 'st-card__img st-card__img--primary',
			'loading' => 'lazy',
			'sizes'   => '(max-width: 60rem) 50vw, 25vw',
		)
	);

	$gallery = $product->get_gallery_image_ids();
	$hover   = $gallery
		? wp_get_attachment_image(
			(int) $gallery[0],
			'woocommerce_thumbnail',
			false,
			array(
				'class'       => 'st-card__img st-card__img--hover',
				'loading'     => 'lazy',
				'aria-hidden' => 'true',
			)
		)
		: '';

	$kicker     = '';
	$categories = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $categories && ! is_wp_error( $categories ) ) {
		$kicker = $categories[0]->name;
	}

	// Simple + purchasable products quick-add via AJAX; variable products
	// open the quick view (size choice without leaving the grid);
	// anything else routes to the product page.
	if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		$quick = sprintf(
			'<button type="button" class="st-card__add" data-st-quick-add="%1$d">%2$s</button>
			<button type="button" class="st-card__add st-card__add--ghost" data-st-quickview="%1$d">%3$s</button>',
			$product->get_id(),
			esc_html__( 'Add to bag', 'skirttique' ),
			esc_html__( 'Quick view', 'skirttique' )
		);
	} elseif ( $product->is_type( 'variable' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		// "Choose size" IS the quick view — one button, no duplicate.
		$quick = sprintf(
			'<button type="button" class="st-card__add" data-st-quickview="%d">%s</button>',
			$product->get_id(),
			esc_html__( 'Choose size', 'skirttique' )
		);
	} else {
		$quick = sprintf(
			'<a class="st-card__add" href="%s">%s</a>',
			esc_url( $permalink ),
			esc_html__( 'View piece', 'skirttique' )
		);
	}

	ob_start();
	?>
	<article class="st-card">
		<div class="st-card__frame">
			<a class="st-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>" tabindex="-1">
				<?php echo $primary . $hover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output. ?>
			</a>
			<div class="st-card__quick"><?php echo $quick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped above. ?></div>
		</div>
		<div class="st-card__meta">
			<?php if ( $kicker ) : ?>
				<p class="st-card__kicker"><?php echo esc_html( $kicker ); ?></p>
			<?php endif; ?>
			<h3 class="st-card__name"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $name ); ?></a></h3>
			<p class="st-card__price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML. ?></p>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * The catalog sort options offered in the shop toolbar.
 *
 * @return array<string, string> orderby value → label.
 */
function catalog_orderings(): array {
	return array(
		'date'       => __( 'Newest', 'skirttique' ),
		'popularity' => __( 'Bestsellers', 'skirttique' ),
		'price'      => __( 'Price, low to high', 'skirttique' ),
		'price-desc' => __( 'Price, high to low', 'skirttique' ),
	);
}

// Card markup for the plugin's fragment endpoints (recently viewed,
// Saved page) — one renderer everywhere.
add_filter(
	'skirttique_product_card_html',
	static fn ( string $html, \WC_Product $product ): string => product_card( $product ),
	10,
	2
);

/**
 * The purchase form — shared by the PDP summary and the quick view.
 *
 * Simple products: price + Add to bag. Variable: one button group per
 * attribute (sizes as squares, not a dropdown), the available-variations
 * map embedded as JSON for pdp.ts to resolve against, price updating per
 * selection, Add disabled until the choice is complete.
 */
function purchase_form( \WC_Product $product ): string {
	$out_of_stock = ! $product->is_purchasable() || ! $product->is_in_stock();

	ob_start();
	?>
	<div
		class="st-buy"
		data-st-purchase
		data-st-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
		<?php if ( $product->is_type( 'variable' ) ) : ?>
			data-st-variations="<?php echo esc_attr( (string) wp_json_encode( array_map( __NAMESPACE__ . '\\slim_variation', $product->get_available_variations() ) ) ); ?>"
		<?php endif; ?>
	>
		<p class="st-buy__price" data-st-price><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML. ?></p>

		<?php if ( $product->is_type( 'variable' ) ) : ?>
			<?php foreach ( $product->get_variation_attributes() as $st_attribute => $st_options ) : ?>
				<?php
				$st_attr_key = 'attribute_' . sanitize_title( $st_attribute );
				$st_label    = wc_attribute_label( $st_attribute, $product );
				?>
				<fieldset class="st-buy__attr" data-st-attr="<?php echo esc_attr( $st_attr_key ); ?>">
					<legend class="st-buy__label"><?php echo esc_html( $st_label ); ?></legend>
					<div class="st-buy__options">
						<?php foreach ( $st_options as $st_option ) : ?>
							<?php
							$st_term       = taxonomy_exists( $st_attribute ) ? get_term_by( 'slug', $st_option, $st_attribute ) : false;
							$st_option_lbl = $st_term ? $st_term->name : $st_option;
							?>
							<button type="button" class="st-buy__option" data-st-option="<?php echo esc_attr( $st_option ); ?>" aria-pressed="false">
								<?php echo esc_html( $st_option_lbl ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		<?php endif; ?>

		<div class="st-buy__actions">
			<?php if ( $out_of_stock ) : ?>
				<button type="button" class="st-btn st-btn--primary st-buy__add" disabled><?php esc_html_e( 'Out of stock', 'skirttique' ); ?></button>
			<?php else : ?>
				<button
					type="button"
					class="st-btn st-btn--primary st-buy__add"
					data-st-add
					<?php disabled( $product->is_type( 'variable' ) ); ?>
					<?php echo $product->is_type( 'variable' ) ? 'data-st-needs-choice' : ''; ?>
				><?php esc_html_e( 'Add to bag', 'skirttique' ); ?></button>
			<?php endif; ?>

			<button type="button" class="st-buy__save" data-st-wishlist-toggle="<?php echo esc_attr( (string) $product->get_id() ); ?>" aria-pressed="false">
				<svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M10 17S2.5 12.5 2.5 7.5A4 4 0 0110 4.6a4 4 0 017.5 2.9C17.5 12.5 10 17 10 17z"/></svg>
				<span data-st-wishlist-label><?php esc_html_e( 'Save', 'skirttique' ); ?></span>
			</button>
		</div>

		<p class="st-buy__note" data-st-buy-note role="status" hidden></p>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Trim a Woo variation array to what pdp.ts actually needs — the full
 * payload embeds gallery/dimension noise into the page.
 *
 * @param array<string, mixed> $variation One entry from get_available_variations().
 * @return array<string, mixed>
 */
function slim_variation( array $variation ): array {
	return array(
		'variation_id' => (int) $variation['variation_id'],
		'attributes'   => $variation['attributes'],
		'is_in_stock'  => (bool) $variation['is_in_stock'],
		'price_html'   => (string) $variation['price_html'],
	);
}

/**
 * PDP accordion panels: description, size & fit, delivery & returns.
 *
 * @return list<array{title: string, content: string}>
 */
function pdp_panels( \WC_Product $product ): array {
	$panels = array();

	$description = $product->get_description();
	if ( $description ) {
		$panels[] = array(
			'title'   => __( 'The piece', 'skirttique' ),
			'content' => wp_kses_post( wpautop( $description ) ),
		);
	}

	$panels[] = array(
		'title'   => __( 'Size & fit', 'skirttique' ),
		'content' => wp_kses_post(
			wpautop(
				sprintf(
					/* translators: %s: size guide URL. */
					__( 'Cut for movement, true to size. Between sizes, take the larger — the waist is where the piece should sit closest. <a href="%s">See the size guide</a>.', 'skirttique' ),
					esc_url( home_url( '/size-guide/' ) )
				)
			)
		),
	);

	$panels[] = array(
		'title'   => __( 'Delivery & returns', 'skirttique' ),
		'content' => wp_kses_post(
			wpautop(
				sprintf(
					/* translators: %s: delivery and returns URL. */
					__( 'Dispatched from Lagos. Local delivery via GIG Logistics; worldwide via DHL. Unworn pieces return within 14 days. <a href="%s">Delivery &amp; returns, in full</a>.', 'skirttique' ),
					esc_url( home_url( '/delivery-returns/' ) )
				)
			)
		),
	);

	/**
	 * Filter the PDP accordion panels.
	 *
	 * @param list<array{title: string, content: string}> $panels  Panels in order.
	 * @param \WC_Product                                 $product The product.
	 */
	return apply_filters( 'skirttique_pdp_panels', $panels, $product );
}

/**
 * Products for the "More from the house" rail: upsells first (curated
 * beats computed), padded to $limit with Woo's related lookup.
 *
 * @return list<\WC_Product>
 */
function companion_products( \WC_Product $product, int $limit = 4 ): array {
	$ids = $product->get_upsell_ids();

	if ( count( $ids ) < $limit ) {
		$related = wc_get_related_products( $product->get_id(), $limit - count( $ids ), $ids );
		$ids     = array_merge( $ids, $related );
	}

	$products = array();
	foreach ( array_slice( $ids, 0, $limit ) as $id ) {
		$companion = wc_get_product( $id );
		if ( $companion && 'publish' === $companion->get_status() && $companion->is_visible() ) {
			$products[] = $companion;
		}
	}

	return $products;
}

/**
 * The account menu, in the house voice: no Downloads (nothing to
 * download), Saved pieces woven in beside Orders, Sign out last.
 *
 * @param array<string, string> $items Endpoint → label.
 * @return array<string, string>
 */
function account_menu_items( array $items ): array {
	unset( $items['downloads'] );

	$items['dashboard']       = __( 'Overview', 'skirttique' );
	$items['customer-logout'] = __( 'Sign out', 'skirttique' );

	// Saved pieces sits directly after Orders.
	$ordered = array();
	foreach ( $items as $endpoint => $label ) {
		$ordered[ $endpoint ] = $label;
		if ( 'orders' === $endpoint ) {
			$ordered['saved-pieces'] = __( 'Saved pieces', 'skirttique' );
		}
	}

	return $ordered;
}
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\\account_menu_items' );

/**
 * 'saved-pieces' is a menu entry, not a real endpoint — send it to the
 * wishlist page.
 *
 * @param string $url      Generated URL.
 * @param string $endpoint Endpoint key.
 */
function account_menu_urls( string $url, string $endpoint ): string {
	if ( 'saved-pieces' === $endpoint ) {
		return home_url( '/saved/' );
	}

	return $url;
}
add_filter( 'woocommerce_get_endpoint_url', __NAMESPACE__ . '\\account_menu_urls', 10, 2 );

/**
 * Quick-view fragment (served through the plugin's endpoint): image,
 * name, price, the shared purchase form, and the road to the full PDP.
 */
function quickview_html( string $html, \WC_Product $product ): string {
	$image = wp_get_attachment_image(
		(int) $product->get_image_id(),
		'woocommerce_single',
		false,
		array( 'class' => 'st-qv__img' )
	);

	$kicker     = '';
	$categories = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $categories && ! is_wp_error( $categories ) ) {
		$kicker = $categories[0]->name;
	}

	ob_start();
	?>
	<article class="st-qv">
		<div class="st-qv__media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output. ?></div>
		<div class="st-qv__summary">
			<?php if ( $kicker ) : ?>
				<p class="st-qv__kicker"><?php echo esc_html( $kicker ); ?></p>
			<?php endif; ?>
			<h2 class="st-qv__name"><?php echo esc_html( $product->get_name() ); ?></h2>
			<?php if ( $product->get_short_description() ) : ?>
				<div class="st-qv__desc"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
			<?php endif; ?>
			<?php echo purchase_form( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in purchase_form(). ?>
			<a class="st-hemline" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'View full details', 'skirttique' ); ?></a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}
add_filter( 'skirttique_quickview_html', __NAMESPACE__ . '\\quickview_html', 10, 2 );
