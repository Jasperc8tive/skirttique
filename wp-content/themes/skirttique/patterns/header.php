<?php
/**
 * Title: Site Header
 * Slug: skirttique/header
 * Categories: skirttique
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * The Skirttique header: announcement bar, logotype, minimal navigation
 * with a collections panel driven by real product categories, and the
 * utility cluster (market selector, search, account, bag). Drawers are
 * native <dialog> elements — focus trap and Esc handling for free.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$st_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$st_cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

/**
 * Filter the rotating announcement messages (placeholder copy until the
 * CMS experience stage makes them editable).
 *
 * @param list<string> $messages Messages, rotated in order.
 */
$st_announcements = apply_filters(
	'skirttique_announcements',
	array(
		__( 'Collection I — midi &amp; maxi, now available', 'skirttique' ),
		__( 'Ships worldwide from Lagos', 'skirttique' ),
		__( 'Defined by elegance. Rooted in modesty.', 'skirttique' ),
	)
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

$st_markets        = class_exists( \Skirttique\Core\Services\Market::class )
	? \Skirttique\Core\Services\Market::all()
	: array( 'NG' => array( 'label' => 'Nigeria', 'currency' => 'NGN', 'symbol' => '₦' ) );
$st_current_code   = class_exists( \Skirttique\Core\Services\Market::class )
	? \Skirttique\Core\Services\Market::current()
	: 'NG';
$st_current_market = $st_markets[ $st_current_code ];
?>

<div class="st-announcement" data-st-rotate>
	<ul class="st-announcement__list">
		<?php foreach ( $st_announcements as $st_i => $st_message ) : ?>
			<li class="st-announcement__item<?php echo 0 === $st_i ? ' is-current' : ''; ?>" data-st-rotate-item>
				<?php echo esc_html( wp_specialchars_decode( $st_message ) ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( count( $st_announcements ) > 1 ) : ?>
		<button type="button" class="st-rotate-pause" data-st-rotate-pause aria-pressed="false" aria-label="<?php esc_attr_e( 'Pause announcements', 'skirttique' ); ?>">
			<svg class="st-rotate-pause__pause" viewBox="0 0 10 10" width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M3 1.5v7M7 1.5v7"/></svg>
			<svg class="st-rotate-pause__play" viewBox="0 0 10 10" width="10" height="10" fill="currentColor" aria-hidden="true"><path d="M2.5 1.2l6 3.8-6 3.8z"/></svg>
		</button>
	<?php endif; ?>
</div>

<header class="st-header" data-st-header>
	<div class="st-header__inner">
		<button type="button" class="st-header__burger" data-st-drawer-open="st-drawer-menu" aria-label="<?php esc_attr_e( 'Open menu', 'skirttique' ); ?>">
			<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 6h16M2 14h16"/></svg>
		</button>

		<a class="st-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		</a>

		<nav class="st-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'skirttique' ); ?>">
			<ul>
				<li><a href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'Shop', 'skirttique' ); ?></a></li>
				<li class="st-header__has-panel">
					<button type="button" data-st-popover="st-collections-panel" aria-expanded="false" aria-controls="st-collections-panel">
						<?php esc_html_e( 'Collections', 'skirttique' ); ?>
					</button>
				</li>
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'skirttique' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Journal', 'skirttique' ); ?></a></li>
			</ul>
		</nav>

		<div class="st-header__actions">
			<div class="st-market">
				<button type="button" class="st-market__toggle" data-st-popover="st-market-panel" aria-expanded="false" aria-controls="st-market-panel">
					<span class="screen-reader-text"><?php esc_html_e( 'Market:', 'skirttique' ); ?></span>
					<?php echo esc_html( $st_current_market['currency'] ); ?>
					<svg viewBox="0 0 10 6" width="10" height="6" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><path d="M1 1l4 4 4-4"/></svg>
				</button>
				<div class="st-popover st-market__panel" id="st-market-panel" hidden>
					<p class="st-popover__label"><?php esc_html_e( 'Shopping in', 'skirttique' ); ?></p>
					<ul>
						<?php foreach ( $st_markets as $st_code => $st_market ) : ?>
							<li>
								<button type="button" data-st-market-option="<?php echo esc_attr( $st_code ); ?>" <?php echo $st_code === $st_current_code ? 'aria-current="true"' : ''; ?>>
									<span><?php echo esc_html( $st_market['label'] ); ?></span>
									<span class="st-market__currency"><?php echo esc_html( $st_market['currency'] . ' ' . $st_market['symbol'] ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<button type="button" class="st-header__icon" data-st-drawer-open="st-drawer-search" aria-label="<?php esc_attr_e( 'Search', 'skirttique' ); ?>">
				<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><circle cx="9" cy="9" r="6.25"/><path d="M13.75 13.75L18 18"/></svg>
			</button>

			<a class="st-header__icon" href="<?php echo esc_url( $st_account_url ); ?>" aria-label="<?php esc_attr_e( 'Account', 'skirttique' ); ?>">
				<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><circle cx="10" cy="6.5" r="3.75"/><path d="M3 17.5c1.2-3.2 3.9-4.75 7-4.75s5.8 1.55 7 4.75"/></svg>
			</a>

			<button type="button" class="st-header__icon st-header__bag" data-st-drawer-open="st-drawer-bag">
				<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M4 6.5h12l-.9 11a1.5 1.5 0 01-1.5 1.4H6.4a1.5 1.5 0 01-1.5-1.4z"/><path d="M7 8.5V5a3 3 0 016 0v3.5"/></svg>
				<?php
				if ( function_exists( 'Skirttique\WooCommerce\cart_count_bubble' ) ) {
					echo Skirttique\WooCommerce\cart_count_bubble(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped in cart_count_bubble().
				}
				if ( function_exists( 'Skirttique\WooCommerce\cart_count_label' ) ) {
					echo Skirttique\WooCommerce\cart_count_label(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped in cart_count_label().
				}
				?>
			</button>
		</div>
	</div>

	<div class="st-popover st-panel" id="st-collections-panel" hidden>
		<div class="st-panel__inner">
			<ul class="st-panel__list">
				<?php foreach ( $st_categories as $st_category ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $st_category ) ); ?>">
							<span class="st-panel__name"><?php echo esc_html( $st_category->name ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="st-panel__aside">
				<figure class="st-panel__figure">
					<?php
					// First collection that has a thumbnail dresses the panel;
					// the hotlink survives only as the no-media fallback.
					$st_panel_img = '';
					foreach ( $st_categories as $st_panel_cat ) {
						$st_panel_thumb = absint( get_term_meta( $st_panel_cat->term_id, 'thumbnail_id', true ) );
						if ( $st_panel_thumb ) {
							$st_panel_img = wp_get_attachment_image( $st_panel_thumb, 'woocommerce_single', false, array( 'loading' => 'lazy' ) );
							break;
						}
					}

					if ( $st_panel_img ) {
						echo $st_panel_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
					} elseif ( \Skirttique\Components\allow_stock_fallbacks() ) {
						echo '<img src="https://images.unsplash.com/photo-1722486245824-7bb0ff9827dc?q=80&amp;w=700&amp;auto=format&amp;fit=crop" alt="" loading="lazy" width="700" height="875">';
					}
					?>
				</figure>
				<a class="st-hemline" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'View everything', 'skirttique' ); ?></a>
			</div>
		</div>
	</div>
</header>

<dialog class="st-drawer st-drawer--search" id="st-drawer-search" aria-label="<?php esc_attr_e( 'Search', 'skirttique' ); ?>">
	<div class="st-drawer__bar">
		<button type="button" class="st-drawer__close" data-st-drawer-close aria-label="<?php esc_attr_e( 'Close search', 'skirttique' ); ?>">
			<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 2l12 12M14 2L2 14"/></svg>
		</button>
	</div>
	<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="st-search" data-st-instant-search>
		<label class="st-search__label" for="st-search-field"><?php esc_html_e( 'Search the house', 'skirttique' ); ?></label>
		<input class="st-search__field" id="st-search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'Maxi, silk, pleated…', 'skirttique' ); ?>" autocomplete="off" aria-describedby="st-search-status">
		<input type="hidden" name="post_type" value="product">
		<button type="submit" class="st-btn st-btn--primary"><?php esc_html_e( 'Search', 'skirttique' ); ?></button>
	</form>
	<div class="st-search__live">
		<p class="st-search__status" id="st-search-status" role="status" data-st-search-status></p>
		<div class="st-search__results" data-st-search-results hidden></div>
		<p class="st-search__all" data-st-search-all hidden><a class="st-hemline" href="#"></a></p>
	</div>
</dialog>

<dialog class="st-drawer st-drawer--side" id="st-drawer-bag" aria-label="<?php esc_attr_e( 'Your bag', 'skirttique' ); ?>">
	<div class="st-drawer__bar">
		<p class="st-drawer__title"><?php esc_html_e( 'Your bag', 'skirttique' ); ?></p>
		<button type="button" class="st-drawer__close" data-st-drawer-close aria-label="<?php esc_attr_e( 'Close bag', 'skirttique' ); ?>">
			<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 2l12 12M14 2L2 14"/></svg>
		</button>
	</div>
	<div class="st-drawer__body">
		<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		<?php endif; ?>
	</div>
	<div class="st-drawer__foot">
		<a class="st-btn st-btn--primary" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>"><?php esc_html_e( 'Checkout', 'skirttique' ); ?></a>
		<a class="st-hemline" href="<?php echo esc_url( $st_cart_url ); ?>"><?php esc_html_e( 'View bag', 'skirttique' ); ?></a>
	</div>
</dialog>

<dialog class="st-drawer st-drawer--side st-drawer--menu" id="st-drawer-menu" aria-label="<?php esc_attr_e( 'Menu', 'skirttique' ); ?>">
	<div class="st-drawer__bar">
		<p class="st-drawer__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		<button type="button" class="st-drawer__close" data-st-drawer-close aria-label="<?php esc_attr_e( 'Close menu', 'skirttique' ); ?>">
			<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 2l12 12M14 2L2 14"/></svg>
		</button>
	</div>
	<div class="st-drawer__body st-menu">
		<nav aria-label="<?php esc_attr_e( 'Mobile', 'skirttique' ); ?>">
			<ul>
				<li><a href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'Shop', 'skirttique' ); ?></a></li>
				<?php foreach ( $st_categories as $st_category ) : ?>
					<li class="st-menu__sub"><a href="<?php echo esc_url( get_term_link( $st_category ) ); ?>"><?php echo esc_html( $st_category->name ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'skirttique' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Journal', 'skirttique' ); ?></a></li>
				<li><a href="<?php echo esc_url( $st_account_url ); ?>"><?php esc_html_e( 'Account', 'skirttique' ); ?></a></li>
			</ul>
		</nav>

		<div class="st-menu__markets">
			<p class="st-menu__markets-label"><?php esc_html_e( 'Shopping in', 'skirttique' ); ?></p>
			<ul>
				<?php foreach ( $st_markets as $st_code => $st_market ) : ?>
					<li>
						<button type="button" data-st-market-option="<?php echo esc_attr( $st_code ); ?>" <?php echo $st_code === $st_current_code ? 'aria-current="true"' : ''; ?>>
							<span><?php echo esc_html( $st_market['label'] ); ?></span>
							<span class="st-menu__markets-currency"><?php echo esc_html( $st_market['currency'] . ' ' . $st_market['symbol'] ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</dialog>
