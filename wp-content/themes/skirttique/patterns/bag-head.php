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
?>

<header class="st-shop__head">
	<p class="st-shop__eyebrow"><?php esc_html_e( 'Your selection', 'skirttique' ); ?></p>
	<h1 class="st-shop__title"><?php esc_html_e( 'The bag', 'skirttique' ); ?></h1>
</header>
