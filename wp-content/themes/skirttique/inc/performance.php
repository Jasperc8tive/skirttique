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
