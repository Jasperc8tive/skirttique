<?php
/**
 * Title: Site Footer
 * Slug: skirttique/footer
 * Categories: skirttique
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * The Skirttique footer on Foliage ground: the house list (newsletter),
 * a hem divider, brand + navigation columns, and the legal bar with the
 * inline market selector. The join form posts to admin-post.php
 * (Skirttique\Core\Services\Newsletter) — no JavaScript required.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$st_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

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

$st_markets      = class_exists( \Skirttique\Core\Services\Market::class )
	? \Skirttique\Core\Services\Market::all()
	: array( 'NG' => array( 'label' => 'Nigeria', 'currency' => 'NGN', 'symbol' => '₦' ) );
$st_current_code = class_exists( \Skirttique\Core\Services\Market::class )
	? \Skirttique\Core\Services\Market::current()
	: 'NG';

/**
 * Filter the footer social links (label => URL). Placeholder URLs until
 * the brand profiles are confirmed in the content stage.
 *
 * @param array<string, string> $links Social links.
 */
$st_social = apply_filters(
	'skirttique_social_links',
	array(
		'Instagram' => '#',
		'TikTok'    => '#',
		'Pinterest' => '#',
	)
);

?>

<div class="st-footer has-foliage-background-color has-nectar-color has-text-color has-background">
	<div class="st-footer__inner">

		<?php
		// The canonical house-list instance — same renderer the
		// Newsletter block uses (inc/components.php).
		echo Skirttique\Components\newsletter( array( 'context' => 'footer' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
		?>

		<svg class="st-hem-divider" viewBox="0 0 1200 12" preserveAspectRatio="none" aria-hidden="true"><path d="M0,6 Q600,-4 1200,6" fill="none" stroke="currentColor" stroke-width="1"/></svg>

		<div class="st-footer__grid">
			<div class="st-footer__brand">
				<p class="st-footer__logotype"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<p class="st-footer__tagline"><?php esc_html_e( 'Defined by elegance. Rooted in modesty.', 'skirttique' ); ?></p>
				<?php if ( $st_social ) : ?>
					<ul class="st-footer__social">
						<?php foreach ( $st_social as $st_label => $st_url ) : ?>
							<li><a class="st-hemline" href="<?php echo esc_url( $st_url ); ?>" rel="noopener"><?php echo esc_html( $st_label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<nav class="st-footer__col" aria-label="<?php esc_attr_e( 'Shop', 'skirttique' ); ?>">
				<p class="st-footer__label"><?php esc_html_e( 'Shop', 'skirttique' ); ?></p>
				<ul>
					<?php foreach ( $st_categories as $st_category ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $st_category ) ); ?>"><?php echo esc_html( $st_category->name ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'View everything', 'skirttique' ); ?></a></li>
				</ul>
			</nav>

			<nav class="st-footer__col" aria-label="<?php esc_attr_e( 'House', 'skirttique' ); ?>">
				<p class="st-footer__label"><?php esc_html_e( 'House', 'skirttique' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Journal', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'skirttique' ); ?></a></li>
				</ul>
			</nav>

			<nav class="st-footer__col" aria-label="<?php esc_attr_e( 'Care', 'skirttique' ); ?>">
				<p class="st-footer__label"><?php esc_html_e( 'Care', 'skirttique' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/saved/' ) ); ?>"><?php esc_html_e( 'Saved pieces', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/size-guide/' ) ); ?>"><?php esc_html_e( 'Size guide', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/faqs/' ) ); ?>"><?php esc_html_e( 'FAQs', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/delivery-returns/' ) ); ?>"><?php esc_html_e( 'Delivery & returns', 'skirttique' ); ?></a></li>
					<li><a href="<?php echo esc_url( $st_account_url ); ?>"><?php esc_html_e( 'Account', 'skirttique' ); ?></a></li>
				</ul>
			</nav>
		</div>
	</div>

	<div class="st-footer__legal">
		<div class="st-footer__legal-inner">
			<p class="st-footer__copyright">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: current year. */
						__( '© %s Skirttique. All rights reserved.', 'skirttique' ),
						gmdate( 'Y' )
					)
				);
				?>
			</p>

			<ul class="st-footer__legal-links">
				<li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'skirttique' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'skirttique' ); ?></a></li>
			</ul>

			<div class="st-footer__markets" role="group" aria-label="<?php esc_attr_e( 'Market', 'skirttique' ); ?>">
				<?php foreach ( $st_markets as $st_code => $st_market ) : ?>
					<button type="button" data-st-market-option="<?php echo esc_attr( $st_code ); ?>" <?php echo $st_code === $st_current_code ? 'aria-current="true"' : ''; ?>>
						<span class="screen-reader-text"><?php echo esc_html( $st_market['label'] ); ?> — </span><?php echo esc_html( $st_market['currency'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<dialog class="st-drawer st-drawer--modal" id="st-drawer-quickview" aria-label="<?php esc_attr_e( 'Quick view', 'skirttique' ); ?>">
	<div class="st-drawer__bar">
		<button type="button" class="st-drawer__close" data-st-drawer-close aria-label="<?php esc_attr_e( 'Close quick view', 'skirttique' ); ?>">
			<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 2l12 12M14 2L2 14"/></svg>
		</button>
	</div>
	<div class="st-drawer__body" data-st-quickview-body></div>
</dialog>
