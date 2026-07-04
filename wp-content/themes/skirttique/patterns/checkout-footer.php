<?php
/**
 * Title: Checkout Footer
 * Slug: skirttique/checkout-footer
 * Categories: skirttique
 * Inserter: no
 *
 * The checkout's quiet close: legal essentials and a line of
 * reassurance — the full footer's navigation would only lead away.
 *
 * @package Skirttique
 */

declare( strict_types=1 );
?>

<footer class="st-checkout-foot">
	<?php echo Skirttique\Components\trust_badges( array( 'badges' => array( 'delivery', 'returns', 'secure' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component. ?>
	<p class="st-checkout-foot__note"><?php esc_html_e( 'Every payment is encrypted end to end. Dispatched from Lagos, delivered worldwide.', 'skirttique' ); ?></p>
	<div class="st-checkout-foot__legal">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'Skirttique', 'skirttique' ); ?></p>
		<ul class="st-checkout-foot__links">
			<li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'skirttique' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'skirttique' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/delivery-returns/' ) ); ?>"><?php esc_html_e( 'Delivery & returns', 'skirttique' ); ?></a></li>
		</ul>
	</div>
</footer>
