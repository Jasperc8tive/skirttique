<?php
/**
 * Performance housekeeping: remove core output the site never uses.
 *
 * Every removal here is additive-safe — nothing below is required by
 * WooCommerce, the editor, or any planned integration.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

namespace Skirttique\Performance;

/**
 * Strip emoji scripts, oEmbed discovery, and generator noise from the head.
 */
function clean_head(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', __NAMESPACE__ . '\\clean_head' );

/**
 * Keep the classic-theme shim stylesheet off block-theme pages.
 */
function dequeue_classic_shims(): void {
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\dequeue_classic_shims', 20 );

// Every WooCommerce surface is restyled by the design system — the
// classic Woo stylesheets (general/layout/smallscreen) would only be
// dead render-blocking weight.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Trim WooCommerce assets per page:
 *  - blocktheme shim: superseded by our tokens/components everywhere.
 *  - wc-blocks-style (~250 KiB): only the block cart/checkout use it.
 *  - wc-add-to-cart: serves Woo's archive add-to-cart buttons, which we
 *    replaced with our own endpoints; cart-fragments does not depend on it.
 */
function trim_woo_assets(): void {
	wp_dequeue_style( 'woocommerce-blocktheme' );

	if ( ! needs_wc_blocks() ) {
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_script( 'wc-add-to-cart' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\trim_woo_assets', 20 );

/**
 * Woo Blocks re-enqueues its stylesheet after wp_enqueue_scripts (block
 * render / enqueue_block_assets), so the trim needs a second pass at
 * print time — and a third for late styles that would otherwise print
 * from the footer.
 */
function trim_woo_assets_late(): void {
	if ( ! needs_wc_blocks() ) {
		wp_dequeue_style( 'wc-blocks-style' );
	}
}
add_action( 'wp_print_styles', __NAMESPACE__ . '\\trim_woo_assets_late', 100 );
add_action( 'wp_print_footer_scripts', __NAMESPACE__ . '\\trim_woo_assets_late', 1 );

/**
 * The block cart/checkout/account pages are the only wc-blocks consumers.
 */
function needs_wc_blocks(): bool {
	return function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
}
