<?php
/**
 * Component renderers — the single source of section markup.
 *
 * Stage 17: every editorial section lives here exactly once. The
 * patterns (homepage, footer) and the Skirttique block library
 * (inc/blocks.php) both render through these functions, so an editor
 * composing blocks and the shipped patterns produce identical markup,
 * styled by the same SCSS components and driven by the same tokens.
 *
 * Every renderer RETURNS escaped HTML (never echoes) and accepts a
 * single $args array with documented keys.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\Components;

/**
 * Shared section head: eyebrow + serif title, drape-revealed.
 *
 * @param array{eyebrow?: string, title?: string, id?: string} $args Args.
 */
function section_head( array $args ): string {
	$eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
	$title   = trim( (string) ( $args['title'] ?? '' ) );
	$id      = trim( (string) ( $args['id'] ?? '' ) );

	if ( '' === $eyebrow && '' === $title ) {
		return '';
	}

	$out  = '<div class="st-drape"><div class="st-section__head">';
	if ( '' !== $eyebrow ) {
		$out .= '<p class="st-section__eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	if ( '' !== $title ) {
		$out .= '<h2 class="st-section__title"' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . esc_html( $title ) . '</h2>';
	}
	$out .= '</div></div>';

	return $out;
}

/**
 * Drape stagger class, cycling 1-4.
 */
function drape_delay( int $i ): string {
	return 'st-drape--delay-' . ( ( $i % 4 ) + 1 );
}

/**
 * Full-bleed hero: media + eyebrow/statement/sub/CTA.
 *
 * @param array{eyebrow?: string, statement?: string, sub?: string, cta_label?: string, cta_url?: string, image_id?: int, fallback_img?: string, heading_level?: int, parallax?: bool} $args Args.
 */
function hero( array $args ): string {
	$image_id = absint( $args['image_id'] ?? 0 );
	$level    = in_array( (int) ( $args['heading_level'] ?? 1 ), array( 1, 2 ), true ) ? (int) ( $args['heading_level'] ?? 1 ) : 1;
	$media    = $image_id
		? wp_get_attachment_image(
			$image_id,
			'woocommerce_single',
			false,
			array(
				'alt'           => '',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		)
		: (string) ( $args['fallback_img'] ?? '' );

	$parallax = ! empty( $args['parallax'] ) ? ' data-st-parallax="0.12"' : '';

	$out  = '<section class="st-hero">';
	$out .= '<div class="st-hero__media"' . $parallax . '>' . $media . '</div>'; // Media built by wp_get_attachment_image or a shipped fallback literal.
	$out .= '<div class="st-drape"><div class="st-hero__content">';

	if ( ! empty( $args['eyebrow'] ) ) {
		$out .= '<p class="st-hero__eyebrow">' . esc_html( (string) $args['eyebrow'] ) . '</p>';
	}
	if ( ! empty( $args['statement'] ) ) {
		$out .= "<h{$level} class=\"st-hero__statement\">" . esc_html( (string) $args['statement'] ) . "</h{$level}>";
	}
	if ( ! empty( $args['sub'] ) ) {
		$out .= '<p class="st-hero__sub">' . esc_html( (string) $args['sub'] ) . '</p>';
	}
	if ( ! empty( $args['cta_label'] ) && ! empty( $args['cta_url'] ) ) {
		$out .= '<a class="st-hemline st-hero__cta" href="' . esc_url( (string) $args['cta_url'] ) . '">' . esc_html( (string) $args['cta_label'] ) . '</a>';
	}

	$out .= '</div></div></section>';

	return $out;
}

/**
 * Editorial split: statement + prose beside a portrait figure
 * (the philosophy layout, generalised).
 *
 * @param array{eyebrow?: string, statement?: string, prose?: string, cta_label?: string, cta_url?: string, image_id?: int, fallback_img?: string, media_side?: string} $args Args.
 */
function editorial( array $args ): string {
	$image_id = absint( $args['image_id'] ?? 0 );
	$media    = $image_id
		? wp_get_attachment_image( $image_id, 'woocommerce_single', false, array( 'alt' => '', 'loading' => 'lazy' ) )
		: (string) ( $args['fallback_img'] ?? '' );

	$flip = ( 'left' === ( $args['media_side'] ?? 'right' ) ) ? ' st-philosophy--media-left' : '';
	$id   = trim( (string) ( $args['id'] ?? '' ) );

	$out  = '<section class="st-section st-philosophy' . esc_attr( $flip ) . '"' . ( '' !== $id ? ' aria-labelledby="' . esc_attr( $id ) . '"' : '' ) . '>';
	$out .= '<div class="st-drape"><div class="st-philosophy__copy">';
	if ( ! empty( $args['eyebrow'] ) ) {
		$out .= '<p class="st-section__eyebrow">' . esc_html( (string) $args['eyebrow'] ) . '</p>';
	}
	if ( ! empty( $args['statement'] ) ) {
		$out .= '<h2 class="st-philosophy__statement"' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . esc_html( (string) $args['statement'] ) . '</h2>';
	}
	if ( ! empty( $args['prose'] ) ) {
		$out .= '<p class="st-philosophy__prose">' . esc_html( (string) $args['prose'] ) . '</p>';
	}
	if ( ! empty( $args['cta_label'] ) && ! empty( $args['cta_url'] ) ) {
		$out .= '<a class="st-hemline" href="' . esc_url( (string) $args['cta_url'] ) . '">' . esc_html( (string) $args['cta_label'] ) . '</a>';
	}
	$out .= '</div></div>';

	if ( '' !== $media ) {
		$out .= '<div class="st-drape st-drape--delay-2"><figure class="st-philosophy__figure">' . $media . '</figure></div>';
	}

	$out .= '</section>';

	return $out;
}

/**
 * Collection cards — product_cat terms as editorial cards.
 *
 * @param array{eyebrow?: string, title?: string, fallback_images?: array<string, string>} $args Args.
 */
function collection_cards( array $args ): string {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}

	$fallbacks = (array) ( $args['fallback_images'] ?? array() );
	$id        = trim( (string) ( $args['id'] ?? '' ) );

	$out  = '<section class="st-section st-collections"' . ( '' !== $id ? ' aria-labelledby="' . esc_attr( $id ) . '"' : '' ) . '>';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '', 'id' => $id ) );
	$out .= '<div class="st-collections__grid">';

	foreach ( $terms as $i => $term ) {
		$frame    = '';
		$thumb_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		if ( $thumb_id ) {
			$frame = wp_get_attachment_image( $thumb_id, 'large', false, array( 'alt' => '', 'loading' => 'lazy' ) );
		} elseif ( isset( $fallbacks[ $term->slug ] ) ) {
			$frame = '<img src="' . esc_url( 'https://images.unsplash.com/' . $fallbacks[ $term->slug ] . '?q=80&w=900&auto=format&fit=crop' ) . '" alt="" loading="lazy" width="900" height="1125">';
		}

		$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '">';
		$out .= '<a class="st-collections__card" href="' . esc_url( (string) get_term_link( $term ) ) . '">';
		$out .= '<span class="st-collections__frame">' . $frame . '</span>';
		$out .= '<span class="st-collections__name">' . esc_html( $term->name ) . '</span>';
		$out .= '</a></div>';
	}

	$out .= '</div></section>';

	return $out;
}

/**
 * Resolve products for grid/slider sections.
 *
 * @param string $source newest|bestsellers|sale|category|handpicked.
 * @param int    $count  Max products.
 * @param string $extra  Category slug or comma-separated ids, per source.
 * @return list<\WC_Product>
 */
function products( string $source, int $count, string $extra = '' ): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$base = array(
		'status' => 'publish',
		'limit'  => max( 1, min( 12, $count ) ),
	);

	switch ( $source ) {
		case 'bestsellers':
			$query = array_merge( $base, array( 'orderby' => 'popularity', 'order' => 'DESC' ) );
			break;
		case 'sale':
			$ids = wc_get_product_ids_on_sale();
			if ( ! $ids ) {
				return array();
			}
			$query = array_merge( $base, array( 'include' => $ids, 'orderby' => 'date', 'order' => 'DESC' ) );
			break;
		case 'category':
			$query = array_merge( $base, array( 'category' => array( sanitize_title( $extra ) ), 'orderby' => 'date', 'order' => 'DESC' ) );
			break;
		case 'handpicked':
			$ids = array_filter( array_map( 'absint', explode( ',', $extra ) ) );
			if ( ! $ids ) {
				return array();
			}
			$query = array_merge( $base, array( 'include' => $ids, 'orderby' => 'post__in' ) );
			break;
		case 'newest':
		default:
			$query = array_merge( $base, array( 'orderby' => 'date', 'order' => 'DESC' ) );
	}

	$found = wc_get_products( $query );

	return array_values( array_filter( $found, static fn ( $p ) => $p instanceof \WC_Product && $p->is_visible() ) );
}

/**
 * Product grid section (the "edit" layout).
 *
 * @param array{eyebrow?: string, title?: string, products: list<\WC_Product>, more_label?: string, more_url?: string} $args Args.
 */
function product_grid( array $args ): string {
	$items = $args['products'] ?? array();
	if ( ! $items || ! function_exists( 'Skirttique\WooCommerce\product_card' ) ) {
		return '';
	}

	$id = trim( (string) ( $args['id'] ?? '' ) );

	$out  = '<section class="st-section st-edit"' . ( '' !== $id ? ' aria-labelledby="' . esc_attr( $id ) . '"' : '' ) . '>';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '', 'id' => $id ) );
	$out .= '<div class="st-edit__grid">';

	foreach ( $items as $i => $product ) {
		$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '">'
			. \Skirttique\WooCommerce\product_card( $product )
			. '</div>';
	}

	$out .= '</div>';

	if ( ! empty( $args['more_label'] ) && ! empty( $args['more_url'] ) ) {
		$out .= '<div class="st-drape"><div class="st-edit__more"><a class="st-hemline" href="' . esc_url( (string) $args['more_url'] ) . '">' . esc_html( (string) $args['more_label'] ) . '</a></div></div>';
	}

	$out .= '</section>';

	return $out;
}

/**
 * Product slider — the grid's horizontal sibling (scroll-snap + arrows).
 *
 * @param array{eyebrow?: string, title?: string, products: list<\WC_Product>} $args Args.
 */
function product_slider( array $args ): string {
	$items = $args['products'] ?? array();
	if ( ! $items || ! function_exists( 'Skirttique\WooCommerce\product_card' ) ) {
		return '';
	}

	$out  = '<section class="st-section st-slider" data-st-slider>';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '' ) );
	$out .= '<div class="st-slider__viewport"><div class="st-slider__track" data-st-slider-track>';

	foreach ( $items as $product ) {
		$out .= '<div class="st-slider__slide">' . \Skirttique\WooCommerce\product_card( $product ) . '</div>';
	}

	$out .= '</div></div>';
	$out .= '<div class="st-slider__nav">'
		. '<button type="button" class="st-slider__arrow" data-st-slider-prev aria-label="' . esc_attr__( 'Previous products', 'skirttique' ) . '"><svg viewBox="0 0 14 12" width="14" height="12" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M6 1L1 6l5 5M1 6h12"/></svg></button>'
		. '<button type="button" class="st-slider__arrow" data-st-slider-next aria-label="' . esc_attr__( 'Next products', 'skirttique' ) . '"><svg viewBox="0 0 14 12" width="14" height="12" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M8 1l5 5-5 5M13 6H1"/></svg></button>'
		. '</div></section>';

	return $out;
}

/**
 * Testimonials — the press band (rotating quotes + WCAG pause control).
 *
 * @param array{eyebrow?: string, quotes: list<array{quote: string, source: string}>, interval?: int} $args Args.
 */
function testimonials( array $args ): string {
	$quotes = array_values(
		array_filter(
			(array) ( $args['quotes'] ?? array() ),
			static fn ( $q ): bool => is_array( $q ) && '' !== trim( (string) ( $q['quote'] ?? '' ) )
		)
	);
	if ( ! $quotes ) {
		return '';
	}

	$interval = max( 4000, absint( $args['interval'] ?? 9000 ) );
	$eyebrow  = trim( (string) ( $args['eyebrow'] ?? __( 'In their words', 'skirttique' ) ) );

	$out  = '<section class="st-press" aria-label="' . esc_attr( $eyebrow ) . '">';
	$out .= '<div class="st-press__inner" data-st-rotate="' . esc_attr( (string) $interval ) . '">';

	if ( count( $quotes ) > 1 ) {
		$out .= '<button type="button" class="st-rotate-pause st-rotate-pause--press" data-st-rotate-pause aria-pressed="false" aria-label="' . esc_attr__( 'Pause quotes', 'skirttique' ) . '">'
			. '<svg class="st-rotate-pause__pause" viewBox="0 0 10 10" width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M3 1.5v7M7 1.5v7"/></svg>'
			. '<svg class="st-rotate-pause__play" viewBox="0 0 10 10" width="10" height="10" fill="currentColor" aria-hidden="true"><path d="M2.5 1.2l6 3.8-6 3.8z"/></svg>'
			. '</button>';
	}

	$out .= '<p class="st-section__eyebrow">' . esc_html( $eyebrow ) . '</p><ul class="st-press__list">';

	foreach ( $quotes as $i => $quote ) {
		$out .= '<li class="st-press__item' . ( 0 === $i ? ' is-current' : '' ) . '" data-st-rotate-item><blockquote class="st-press__blockquote">'
			. '<p class="st-press__quote">' . esc_html( (string) $quote['quote'] ) . '</p>';
		if ( ! empty( $quote['source'] ) ) {
			$out .= '<cite class="st-press__source">' . esc_html( (string) $quote['source'] ) . '</cite>';
		}
		$out .= '</blockquote></li>';
	}

	$out .= '</ul></div></section>';

	return $out;
}

/**
 * CTA band — statement on Foliage ground with a hemline action.
 *
 * @param array{statement?: string, cta_label?: string, cta_url?: string} $args Args.
 */
function cta_band( array $args ): string {
	$out  = '<section class="st-section st-closing has-foliage-background-color has-nectar-color has-text-color has-background">';
	$out .= '<div class="st-drape"><div class="st-closing__inner">';
	if ( ! empty( $args['statement'] ) ) {
		$out .= '<h2 class="st-closing__statement">' . esc_html( (string) $args['statement'] ) . '</h2>';
	}
	if ( ! empty( $args['cta_label'] ) && ! empty( $args['cta_url'] ) ) {
		$out .= '<a class="st-hemline" href="' . esc_url( (string) $args['cta_url'] ) . '">' . esc_html( (string) $args['cta_label'] ) . '</a>';
	}
	$out .= '</div></div></section>';

	return $out;
}

/**
 * Newsletter join form (the house list). The footer owns the canonical
 * `st-house-list` instance; block instances get suffixed ids so two on
 * one page never collide.
 *
 * @param array{eyebrow?: string, title?: string, promise?: string, context?: string} $args Args.
 */
function newsletter( array $args ): string {
	$suffix = 'footer' === ( $args['context'] ?? 'block' ) ? '' : '-' . wp_unique_id();
	$id     = 'st-house-list' . $suffix;

	$joined = isset( $_GET['st-joined'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
	$error  = isset( $_GET['st-join-error'] ) ? sanitize_key( wp_unslash( $_GET['st-join-error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.

	$out  = '<section class="st-footer__news" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-title">';
	$out .= '<div class="st-footer__news-copy">';
	$out .= '<p class="st-footer__eyebrow">' . esc_html( (string) ( $args['eyebrow'] ?? __( 'The house list', 'skirttique' ) ) ) . '</p>';
	$out .= '<h2 class="st-footer__statement" id="' . esc_attr( $id ) . '-title">' . esc_html( (string) ( $args['title'] ?? __( 'First to every collection', 'skirttique' ) ) ) . '</h2>';
	$out .= '<p class="st-footer__promise">' . esc_html( (string) ( $args['promise'] ?? __( 'New pieces, private previews and the journal — a few letters a season, nothing more.', 'skirttique' ) ) ) . '</p>';
	$out .= '</div>';

	if ( $joined ) {
		$out .= '<p class="st-footer__note" role="status">' . esc_html__( 'You are on the list. Welcome to the house.', 'skirttique' ) . '</p>';
	} else {
		$out .= '<form class="st-join" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="skirttique_join">'
			. wp_nonce_field( 'skirttique_join', '_wpnonce', true, false )
			. '<p class="st-join__hp" aria-hidden="true"><label for="st-join-website' . esc_attr( $suffix ) . '">' . esc_html__( 'Website', 'skirttique' ) . '</label>'
			. '<input type="text" name="st_website" id="st-join-website' . esc_attr( $suffix ) . '" tabindex="-1" autocomplete="off"></p>'
			. '<div class="st-join__row">'
			. '<label class="screen-reader-text" for="st-join-email' . esc_attr( $suffix ) . '">' . esc_html__( 'Email address', 'skirttique' ) . '</label>'
			. '<input class="st-join__field" type="email" name="st_email" id="st-join-email' . esc_attr( $suffix ) . '" placeholder="name@example.com" autocomplete="email" required'
			. ( 'email' === $error ? ' aria-describedby="st-join-error' . esc_attr( $suffix ) . '" aria-invalid="true"' : '' ) . '>'
			. '<button type="submit" class="st-btn st-btn--primary">' . esc_html__( 'Join the house list', 'skirttique' ) . '</button>'
			. '</div>';

		if ( 'email' === $error ) {
			$out .= '<p class="st-join__error" id="st-join-error' . esc_attr( $suffix ) . '" role="alert">' . esc_html__( 'Enter a full email address, like name@example.com.', 'skirttique' ) . '</p>';
		} elseif ( 'expired' === $error ) {
			$out .= '<p class="st-join__error" role="alert">' . esc_html__( 'That took a little long and the form timed out — please try again.', 'skirttique' ) . '</p>';
		}

		$out .= '</form>';
	}

	$out .= '</section>';

	return $out;
}

/**
 * Standalone newsletter section (block context): the footer band styles
 * on a Foliage ground, outside the footer.
 */
function newsletter_band( array $args ): string {
	return '<section class="st-newsletter-band has-foliage-background-color has-nectar-color has-text-color has-background"><div class="st-newsletter-band__inner">'
		. newsletter( array_merge( $args, array( 'context' => 'block' ) ) )
		. '</div></section>';
}

/**
 * Statistics — a quiet row of numbers with labels.
 *
 * @param array{eyebrow?: string, title?: string, items: list<array{value: string, label: string}>} $args Args.
 */
function stats( array $args ): string {
	$items = (array) ( $args['items'] ?? array() );
	if ( ! $items ) {
		return '';
	}

	$out  = '<section class="st-section st-stats">';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '' ) );
	$out .= '<dl class="st-stats__row">';

	foreach ( $items as $i => $item ) {
		$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '"><div class="st-stats__item">'
			. '<dt class="st-stats__label">' . esc_html( (string) ( $item['label'] ?? '' ) ) . '</dt>'
			. '<dd class="st-stats__value">' . esc_html( (string) ( $item['value'] ?? '' ) ) . '</dd>'
			. '</div></div>';
	}

	$out .= '</dl></section>';

	return $out;
}

/**
 * Feature list — title/description pairs on hairline rows.
 *
 * @param array{eyebrow?: string, title?: string, items: list<array{title: string, text: string}>} $args Args.
 */
function feature_list( array $args ): string {
	$items = (array) ( $args['items'] ?? array() );
	if ( ! $items ) {
		return '';
	}

	$out  = '<section class="st-section st-features">';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '' ) );
	$out .= '<div class="st-features__list">';

	foreach ( $items as $i => $item ) {
		$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '"><div class="st-features__item">'
			. '<h3 class="st-features__name">' . esc_html( (string) ( $item['title'] ?? '' ) ) . '</h3>'
			. '<p class="st-features__text">' . esc_html( (string) ( $item['text'] ?? '' ) ) . '</p>'
			. '</div></div>';
	}

	$out .= '</div></section>';

	return $out;
}

/**
 * Trust badges — the store's promises as a hairline strip.
 *
 * @param array{badges?: list<string>} $args Keys of badges to show.
 */
function trust_badges( array $args ): string {
	$catalog = array(
		'delivery' => array(
			'label' => __( 'Worldwide delivery from Lagos', 'skirttique' ),
			'icon'  => '<path d="M2 13h11l3-4h2.5a1.5 1.5 0 011.5 1.5V13M2 13V6.5A1.5 1.5 0 013.5 5H11a2 2 0 012 2v6M5.5 16a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>',
		),
		'returns'  => array(
			'label' => __( '14-day returns', 'skirttique' ),
			'icon'  => '<path d="M3 9a7 7 0 111.5 4.33M3 9V4m0 5h5"/>',
		),
		'secure'   => array(
			'label' => __( 'Secure checkout', 'skirttique' ),
			'icon'  => '<path d="M5 9V6.5a5 5 0 0110 0V9M4.5 9h11a1 1 0 011 1v6.5a1 1 0 01-1 1h-11a1 1 0 01-1-1V10a1 1 0 011-1z"/>',
		),
		'made'     => array(
			'label' => __( 'Made in limited runs', 'skirttique' ),
			'icon'  => '<path d="M10 2l2.35 4.76L17.6 7.5l-3.8 3.7.9 5.23L10 14l-4.7 2.43.9-5.23-3.8-3.7 5.25-.74z"/>',
		),
	);

	$keys = array_values( array_intersect( (array) ( $args['badges'] ?? array_keys( $catalog ) ), array_keys( $catalog ) ) );
	if ( ! $keys ) {
		return '';
	}

	$out = '<div class="st-trust"><ul class="st-trust__list">';
	foreach ( $keys as $key ) {
		$out .= '<li class="st-trust__item">'
			. '<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.1" aria-hidden="true">' . $catalog[ $key ]['icon'] . '</svg>'
			. '<span>' . esc_html( $catalog[ $key ]['label'] ) . '</span></li>';
	}
	$out .= '</ul></div>';

	return $out;
}

/**
 * Gallery — editorial image grid with drape reveals and optional zoom.
 *
 * @param array{image_ids?: list<int>, columns?: int, zoom?: bool} $args Args.
 */
function gallery( array $args ): string {
	$ids = array_filter( array_map( 'absint', (array) ( $args['image_ids'] ?? array() ) ) );
	if ( ! $ids ) {
		return '';
	}

	$columns = max( 2, min( 4, absint( $args['columns'] ?? 3 ) ) );
	$zoom    = ! empty( $args['zoom'] ) ? ' data-st-zoom' : '';

	$out = '<section class="st-section st-gallery st-gallery--cols-' . esc_attr( (string) $columns ) . '"><div class="st-gallery__grid">';
	foreach ( $ids as $i => $id ) {
		$img = wp_get_attachment_image( $id, 'large', false, array( 'loading' => 'lazy' ) );
		if ( $img ) {
			$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '"><figure class="st-gallery__figure"' . $zoom . '>' . $img . '</figure></div>';
		}
	}
	$out .= '</div></section>';

	return $out;
}

/**
 * Video — a poster-first editorial film. Ambient mode = muted loop,
 * paused for prefers-reduced-motion (media.ts guards it).
 *
 * @param array{video_url?: string, poster_id?: int, ambient?: bool, caption?: string} $args Args.
 */
function video( array $args ): string {
	$src = esc_url( (string) ( $args['video_url'] ?? '' ) );
	if ( '' === $src ) {
		return '';
	}

	$poster_id  = absint( $args['poster_id'] ?? 0 );
	$poster_src = $poster_id ? (string) wp_get_attachment_image_url( $poster_id, 'woocommerce_single' ) : '';
	$ambient    = ! empty( $args['ambient'] );

	$attrs = $ambient
		? ' muted loop playsinline autoplay data-st-ambient'
		: ' controls preload="metadata"';

	$out  = '<section class="st-section st-video"><figure class="st-video__frame st-drape"><div>';
	$out .= '<video class="st-video__media"' . $attrs . ( $poster_src ? ' poster="' . esc_url( $poster_src ) . '"' : '' ) . '>';
	$out .= '<source src="' . $src . '" type="video/mp4">';
	$out .= '</video></div>';
	if ( ! empty( $args['caption'] ) ) {
		$out .= '<figcaption class="st-video__caption">' . esc_html( (string) $args['caption'] ) . '</figcaption>';
	}
	$out .= '</figure></section>';

	return $out;
}

/**
 * Pricing — bespoke tiers on hairline cards (Custom Orders page).
 *
 * @param array{eyebrow?: string, title?: string, items: list<array{tier: string, price: string, text: string}>} $args Args.
 */
function pricing( array $args ): string {
	$items = (array) ( $args['items'] ?? array() );
	if ( ! $items ) {
		return '';
	}

	$out  = '<section class="st-section st-pricing">';
	$out .= section_head( array( 'eyebrow' => $args['eyebrow'] ?? '', 'title' => $args['title'] ?? '' ) );
	$out .= '<div class="st-pricing__row">';

	foreach ( $items as $i => $item ) {
		$out .= '<div class="st-drape ' . esc_attr( drape_delay( $i ) ) . '"><div class="st-pricing__card">'
			. '<p class="st-pricing__tier">' . esc_html( (string) ( $item['tier'] ?? '' ) ) . '</p>'
			. '<p class="st-pricing__price">' . esc_html( (string) ( $item['price'] ?? '' ) ) . '</p>'
			. '<p class="st-pricing__text">' . esc_html( (string) ( $item['text'] ?? '' ) ) . '</p>'
			. '</div></div>';
	}

	$out .= '</div></section>';

	return $out;
}

/**
 * Breadcrumbs — generic trail for pages/terms (the PDP keeps its own).
 */
function breadcrumbs(): string {
	$trail = array( array( 'label' => __( 'Home', 'skirttique' ), 'url' => home_url( '/' ) ) );

	$object = get_queried_object();
	if ( $object instanceof \WP_Term ) {
		$trail[] = array( 'label' => $object->name, 'url' => '' );
	} elseif ( $object instanceof \WP_Post ) {
		if ( $object->post_parent ) {
			$parent = get_post( $object->post_parent );
			if ( $parent ) {
				$trail[] = array( 'label' => get_the_title( $parent ), 'url' => (string) get_permalink( $parent ) );
			}
		}
		$trail[] = array( 'label' => get_the_title( $object ), 'url' => '' );
	} else {
		return '';
	}

	$out  = '<nav class="st-crumbs" aria-label="' . esc_attr__( 'You are here', 'skirttique' ) . '">';
	$last = count( $trail ) - 1;
	foreach ( $trail as $i => $crumb ) {
		if ( $i === $last || '' === $crumb['url'] ) {
			$out .= '<span aria-current="page">' . esc_html( $crumb['label'] ) . '</span>';
		} else {
			$out .= '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a><span aria-hidden="true">/</span>';
		}
	}
	$out .= '</nav>';

	return $out;
}

/**
 * Parse "A|B" textarea lines into keyed pairs — the storage format the
 * block sidebar uses for simple repeaters.
 *
 * @return list<array<string, string>>
 */
function parse_lines( string $raw, array $keys ): array {
	$items = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) ?: array() as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, count( $keys ) ) );
		$item  = array();
		foreach ( $keys as $i => $key ) {
			$item[ $key ] = $parts[ $i ] ?? '';
		}
		$items[] = $item;
	}

	return $items;
}
