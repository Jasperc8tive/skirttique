<?php
/**
 * Title: Bag Head
 * Slug: skirttique/bag-head
 * Categories: skirttique
 * Inserter: no
 *
 * Editorial head for the bag (cart) page — the cart block itself comes
 * from the page content via templates/page-cart.html.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

// Delivery-on-the-house progress (Stage 24). Shown only in the NG
// market: the free-shipping threshold is configured in NGN, and Woo
// compares it against converted totals — cross-market maths would lie.
$st_threshold = function_exists( 'Skirttique\WooCommerce\free_shipping_threshold' )
	? Skirttique\WooCommerce\free_shipping_threshold()
	: 0.0;
$st_is_ng     = ! class_exists( \Skirttique\Core\Services\Market::class )
	|| 'NG' === \Skirttique\Core\Services\Market::current();
$st_subtotal  = function_exists( 'WC' ) && WC()->cart ? (float) WC()->cart->get_subtotal() : 0.0;
?>

<header class="st-shop__head">
	<p class="st-shop__eyebrow"><?php esc_html_e( 'Your selection', 'skirttique' ); ?></p>
	<h1 class="st-shop__title"><?php esc_html_e( 'The bag', 'skirttique' ); ?></h1>

	<?php if ( $st_threshold > 0 && $st_is_ng && $st_subtotal > 0 ) : ?>
		<?php $st_remaining = max( 0.0, $st_threshold - $st_subtotal ); ?>
		<div class="st-ship" data-st-ship-progress data-st-ship-threshold="<?php echo esc_attr( (string) $st_threshold ); ?>">
			<p class="st-ship__note" data-st-ship-note aria-live="polite">
				<?php
				if ( $st_remaining > 0 ) {
					printf(
						/* translators: %s: amount remaining. */
						esc_html__( '%s away from delivery on the house.', 'skirttique' ),
						wp_kses_post( wc_price( $st_remaining ) )
					);
				} else {
					esc_html_e( 'Delivery on the house — you are there.', 'skirttique' );
				}
				?>
			</p>
			<div class="st-ship__track" aria-hidden="true">
				<div class="st-ship__bar" data-st-ship-bar style="width: <?php echo esc_attr( (string) min( 100, round( $st_subtotal / $st_threshold * 100 ) ) ); ?>%"></div>
			</div>
		</div>
	<?php endif; ?>
</header>
