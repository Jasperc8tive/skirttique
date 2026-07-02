<?php
/**
 * Title: Saved Pieces
 * Slug: skirttique/saved
 * Categories: skirttique
 * Inserter: no
 *
 * The wishlist page. The grid is rendered client-side (wishlist.ts):
 * guests read localStorage, customers read their account list — both
 * fetch card markup from the plugin's product-cards endpoint. The
 * empty state is the server-rendered default; JS reveals the grid
 * only when there is something to show.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<main id="main-content" class="st-shop">
	<header class="st-shop__head">
		<p class="st-shop__eyebrow"><?php esc_html_e( 'Your wishlist', 'skirttique' ); ?></p>
		<h1 class="st-shop__title"><?php esc_html_e( 'Saved pieces', 'skirttique' ); ?></h1>
		<?php if ( is_user_logged_in() ) : ?>
			<p class="st-shop__lede"><?php esc_html_e( 'Kept with your account, on every device.', 'skirttique' ); ?></p>
		<?php else : ?>
			<p class="st-shop__lede"><?php esc_html_e( 'Kept here on this device — sign in to keep them everywhere.', 'skirttique' ); ?></p>
		<?php endif; ?>
	</header>

	<div class="st-shop__grid" data-st-saved-grid hidden></div>

	<div class="st-shop__empty" data-st-saved-empty>
		<p class="st-shop__empty-note"><?php esc_html_e( 'Nothing saved yet.', 'skirttique' ); ?></p>
		<a class="st-hemline" href="<?php echo esc_url( $st_shop_url ); ?>"><?php esc_html_e( 'View everything', 'skirttique' ); ?></a>
	</div>
</main>
