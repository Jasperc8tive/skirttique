<?php
/**
 * Title: Order Head
 * Slug: skirttique/order-head
 * Categories: skirttique
 * Inserter: no
 *
 * The Order Success welcome (Stage 24): the house's thank-you and what
 * happens next, above WooCommerce's confirmation blocks.
 *
 * @package Skirttique
 */

declare( strict_types=1 );
?>

<header class="st-order-head">
	<p class="st-section__eyebrow"><?php esc_html_e( 'Order received', 'skirttique' ); ?></p>
	<h1 class="st-order-head__title"><?php esc_html_e( 'Thank you — it is in the atelier’s hands now.', 'skirttique' ); ?></h1>
</header>

<?php
echo Skirttique\Components\feature_list( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
	array(
		'eyebrow' => __( 'What happens next', 'skirttique' ),
		'items'   => array(
			array(
				'title' => __( 'A confirmation, now', 'skirttique' ),
				'text'  => __( 'Your order and receipt are on their way to your inbox.', 'skirttique' ),
			),
			array(
				'title' => __( 'Dispatch from Lagos', 'skirttique' ),
				'text'  => __( 'Ready-to-wear pieces leave the house within 2–4 working days.', 'skirttique' ),
			),
			array(
				'title' => __( 'Tracking to follow', 'skirttique' ),
				'text'  => __( 'The moment it ships, the tracking number lands in the same inbox.', 'skirttique' ),
			),
		),
	)
);
?>
