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
 * PDP accordion panels: description, story, fabric, care, size & fit,
 * delivery & returns. Story/fabric/care read the Stage 21 product meta
 * (keys mirror Skirttique\Core\Services\ProductEditorial — the plugin
 * owns the fields, the theme reads them); story and fabric hide when
 * blank, care falls back to the house note.
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

	$story = trim( (string) $product->get_meta( '_st_story' ) );
	if ( '' !== $story ) {
		$panels[] = array(
			'title'   => __( 'The story', 'skirttique' ),
			'content' => wp_kses_post( wpautop( $story ) ),
		);
	}

	$fabric = trim( (string) $product->get_meta( '_st_fabric' ) );
	if ( '' !== $fabric ) {
		$panels[] = array(
			'title'   => __( 'Fabric & composition', 'skirttique' ),
			'content' => wp_kses_post( wpautop( $fabric ) ),
		);
	}

	$care = trim( (string) $product->get_meta( '_st_care' ) );
	if ( '' === $care ) {
		$care = __( 'Treat it like the investment it is: dry-clean, or a cold, gentle hand-wash where the care label allows. Steam rather than iron, and hang to rest between wears.', 'skirttique' );
	}
	$panels[] = array(
		'title'   => __( 'Care', 'skirttique' ),
		'content' => wp_kses_post( wpautop( $care ) ),
	);

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
 * Products worn with this one (Stage 21): curated cross-sells first,
 * padded from the piece's own collection — distinct from the upsell
 * rail below ("more from the house" is browsing; this is pairing).
 *
 * @return list<\WC_Product>
 */
function paired_products( \WC_Product $product, int $limit = 2 ): array {
	$ids = $product->get_cross_sell_ids();

	if ( count( $ids ) < $limit ) {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		$slug  = $terms && ! is_wp_error( $terms ) ? $terms[0]->slug : '';
		if ( '' !== $slug ) {
			$same_collection = wc_get_products(
				array(
					'status'   => 'publish',
					'limit'    => $limit + 2,
					'category' => array( $slug ),
					'orderby'  => 'date',
					'order'    => 'DESC',
					'exclude'  => array_merge( array( $product->get_id() ), $ids ),
					'return'   => 'ids',
				)
			);
			$ids             = array_merge( $ids, array_map( 'intval', $same_collection ) );
		}
	}

	$products = array();
	foreach ( $ids as $id ) {
		if ( count( $products ) >= $limit ) {
			break;
		}
		$paired = wc_get_product( $id );
		if ( $paired && 'publish' === $paired->get_status() && $paired->is_visible() ) {
			$products[] = $paired;
		}
	}

	return $products;
}

/**
 * "Worn with" — compact pairing rows in the PDP summary. Simple pieces
 * add straight to the bag (the delegated quick-add handler); variable
 * pieces link through to choose a size.
 */
function product_pairing( \WC_Product $product ): string {
	$paired = paired_products( $product );
	if ( ! $paired ) {
		return '';
	}

	$out  = '<aside class="st-pairing" aria-labelledby="st-pairing-title">';
	$out .= '<h2 class="st-pairing__title" id="st-pairing-title">' . esc_html__( 'Worn with', 'skirttique' ) . '</h2>';
	$out .= '<ul class="st-pairing__list">';

	foreach ( $paired as $piece ) {
		$permalink = (string) $piece->get_permalink();

		$out .= '<li class="st-pairing__item">';
		$out .= '<a class="st-pairing__media" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">'
			. $piece->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) )
			. '</a>';
		$out .= '<div class="st-pairing__meta">';
		$out .= '<a class="st-pairing__name" href="' . esc_url( $permalink ) . '">' . esc_html( $piece->get_name() ) . '</a>';
		$out .= '<span class="st-pairing__price">' . wp_kses_post( $piece->get_price_html() ) . '</span>';
		$out .= '</div>';

		if ( $piece->is_type( 'simple' ) && $piece->is_purchasable() && $piece->is_in_stock() ) {
			$out .= '<button type="button" class="st-pairing__add" data-st-quick-add="' . esc_attr( (string) $piece->get_id() ) . '">'
				. esc_html__( 'Add', 'skirttique' )
				. '<span class="screen-reader-text"> — ' . esc_html( $piece->get_name() ) . '</span></button>';
		} else {
			$out .= '<a class="st-pairing__add st-pairing__add--link" href="' . esc_url( $permalink ) . '">' . esc_html__( 'View', 'skirttique' )
				. '<span class="screen-reader-text"> — ' . esc_html( $piece->get_name() ) . '</span></a>';
		}

		$out .= '</li>';
	}

	$out .= '</ul></aside>';

	return $out;
}

/**
 * Star row for a rating, with a screen-reader sentence.
 */
function rating_stars( float $rating, int $max = 5 ): string {
	$out = '<span class="st-stars" role="img" aria-label="' . esc_attr(
		sprintf(
			/* translators: 1: rating, 2: maximum rating. */
			__( 'Rated %1$s of %2$d', 'skirttique' ),
			number_format_i18n( $rating, 1 ),
			$max
		)
	) . '">';

	for ( $i = 1; $i <= $max; $i++ ) {
		$fill = $rating >= ( $i - 0.25 ) ? 'currentColor' : 'none';
		$out .= '<svg viewBox="0 0 20 20" width="14" height="14" fill="' . esc_attr( $fill ) . '" stroke="currentColor" stroke-width="1.1" aria-hidden="true"><path d="M10 2l2.35 4.76 5.25.74-3.8 3.7.9 5.23L10 14l-4.7 2.43.9-5.23-3.8-3.7 5.25-.74z"/></svg>';
	}

	return $out . '</span>';
}

/**
 * Reviews (Stage 21): the house-styled list + rating form. Standard
 * WordPress comments underneath — the form posts to
 * wp-comments-post.php and WooCommerce classifies it as a review and
 * stores the rating (its preprocess_comment/comment_post hooks). New
 * reviews follow the site's moderation settings.
 */
function product_reviews( \WC_Product $product ): string {
	if ( 'yes' !== get_option( 'woocommerce_enable_reviews', 'yes' ) || ! comments_open( $product->get_id() ) ) {
		return '';
	}

	$reviews = get_comments(
		array(
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
		)
	);
	$count   = is_array( $reviews ) ? count( $reviews ) : 0;
	$average = (float) $product->get_average_rating();

	$out  = '<section class="st-reviews" id="reviews" aria-labelledby="st-reviews-title">';
	$out .= '<div class="st-section__head"><p class="st-section__eyebrow">' . esc_html__( 'From the clients', 'skirttique' ) . '</p>';
	$out .= '<h2 class="st-section__title st-pdp__rail-title" id="st-reviews-title">' . esc_html__( 'Reviews', 'skirttique' ) . '</h2></div>';

	if ( $count > 0 ) {
		$out .= '<p class="st-reviews__summary">' . rating_stars( $average ) . ' <span>' . esc_html(
			sprintf(
				/* translators: 1: average rating, 2: review count. */
				_n( '%1$s — %2$d review', '%1$s — %2$d reviews', $count, 'skirttique' ),
				number_format_i18n( $average, 1 ),
				$count
			)
		) . '</span></p>';

		$out .= '<ul class="st-reviews__list">';
		foreach ( $reviews as $review ) {
			$rating   = (float) get_comment_meta( (int) $review->comment_ID, 'rating', true );
			$verified = function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( (int) $review->comment_ID );

			$out .= '<li class="st-reviews__item">';
			if ( $rating > 0 ) {
				$out .= rating_stars( $rating );
			}
			$out .= '<blockquote class="st-reviews__quote">' . wp_kses_post( wpautop( $review->comment_content ) ) . '</blockquote>';
			$out .= '<p class="st-reviews__by"><cite>' . esc_html( $review->comment_author ) . '</cite>';
			if ( $verified ) {
				$out .= ' <span class="st-reviews__verified">' . esc_html__( 'Verified owner', 'skirttique' ) . '</span>';
			}
			$out .= ' <time datetime="' . esc_attr( (string) mysql2date( 'c', $review->comment_date_gmt, false ) ) . '">' . esc_html( (string) mysql2date( get_option( 'date_format' ), $review->comment_date ) ) . '</time></p>';
			$out .= '</li>';
		}
		$out .= '</ul>';
	} else {
		$out .= '<p class="st-reviews__none">' . esc_html__( 'No reviews yet — this piece is waiting for its first word.', 'skirttique' ) . '</p>';
	}

	// The form: guests need name + email; signed-in clients just write.
	$commenter = wp_get_current_commenter();

	$out .= '<form class="st-review-form" method="post" action="' . esc_url( site_url( '/wp-comments-post.php' ) ) . '">';
	$out .= '<h3 class="st-review-form__title">' . esc_html__( 'Leave a review', 'skirttique' ) . '</h3>';
	$out .= '<input type="hidden" name="comment_post_ID" value="' . esc_attr( (string) $product->get_id() ) . '">';
	$out .= '<input type="hidden" name="comment_parent" value="0">';

	$out .= '<fieldset class="st-review-form__rating"><legend>' . esc_html__( 'Your rating', 'skirttique' ) . '</legend><div class="st-review-form__stars">';
	for ( $i = 5; $i >= 1; $i-- ) {
		$out .= '<input type="radio" name="rating" id="st-rating-' . $i . '" value="' . $i . '" required>';
		$out .= '<label for="st-rating-' . $i . '">' . esc_html(
			sprintf(
				/* translators: %d: star rating. */
				_n( '%d star', '%d stars', $i, 'skirttique' ),
				$i
			)
		) . '</label>';
	}
	$out .= '</div></fieldset>';

	if ( ! is_user_logged_in() ) {
		$out .= '<div class="st-review-form__row">';
		$out .= '<p><label for="st-review-author">' . esc_html__( 'Name', 'skirttique' ) . '</label>'
			. '<input type="text" name="author" id="st-review-author" value="' . esc_attr( $commenter['comment_author'] ) . '" autocomplete="name" required></p>';
		$out .= '<p><label for="st-review-email">' . esc_html__( 'Email (never shown)', 'skirttique' ) . '</label>'
			. '<input type="email" name="email" id="st-review-email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" autocomplete="email" required></p>';
		$out .= '</div>';
	}

	$out .= '<p><label for="st-review-comment">' . esc_html__( 'Your review', 'skirttique' ) . '</label>'
		. '<textarea name="comment" id="st-review-comment" rows="5" required></textarea></p>';
	$out .= '<p class="st-review-form__actions"><button type="submit" class="st-btn st-btn--primary">' . esc_html__( 'Submit review', 'skirttique' ) . '</button>';
	$out .= '<span class="st-review-form__note">' . esc_html__( 'Reviews appear once the house approves them.', 'skirttique' ) . '</span></p>';
	$out .= '</form></section>';

	return $out;
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

/**
 * The "on sale" catalog facet (?on_sale=1) — Stage 22. Size and price
 * facets ride WooCommerce's native main-query params; this one is ours.
 */
function apply_sale_facet( \WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || empty( $_GET['on_sale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only facet.
		return;
	}

	$is_catalog = $query->is_post_type_archive( 'product' )
		|| $query->is_tax( 'product_cat' )
		|| ( $query->is_search() && 'product' === $query->get( 'post_type' ) );
	if ( ! $is_catalog || ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
		return;
	}

	$ids = array_map( 'absint', wc_get_product_ids_on_sale() );
	$query->set( 'post__in', $ids ? $ids : array( 0 ) ); // array(0) = deliberately empty result.
}
add_action( 'pre_get_posts', __NAMESPACE__ . '\\apply_sale_facet' );
