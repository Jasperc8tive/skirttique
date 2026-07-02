<?php
/**
 * Title: Account Head
 * Slug: skirttique/account-head
 * Categories: skirttique
 * Inserter: no
 *
 * Editorial head for the account area. Signed out it introduces the
 * sign-in; signed in it greets by name — the account content itself
 * comes from the page's [woocommerce_my_account] shortcode.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_signed_in = is_user_logged_in();
$st_name      = $st_signed_in ? wp_get_current_user()->first_name : '';
?>

<header class="st-shop__head">
	<p class="st-shop__eyebrow"><?php esc_html_e( 'The house', 'skirttique' ); ?></p>
	<h1 class="st-shop__title"><?php esc_html_e( 'My account', 'skirttique' ); ?></h1>
	<?php if ( ! $st_signed_in ) : ?>
		<p class="st-shop__lede"><?php esc_html_e( 'Sign in for your orders, addresses, and saved pieces — kept on every device.', 'skirttique' ); ?></p>
	<?php elseif ( $st_name ) : ?>
		<p class="st-shop__lede">
			<?php
			/* translators: %s: customer first name. */
			printf( esc_html__( 'Welcome back, %s.', 'skirttique' ), esc_html( $st_name ) );
			?>
		</p>
	<?php endif; ?>
</header>
