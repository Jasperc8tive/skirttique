<?php
/**
 * Skirttique theme bootstrap.
 *
 * Thin loader only — all behaviour lives in small, focused files under inc/.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

define( 'SKIRTTIQUE_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'SKIRTTIQUE_DIR', get_template_directory() );
define( 'SKIRTTIQUE_URI', get_template_directory_uri() );

require SKIRTTIQUE_DIR . '/inc/setup.php';
require SKIRTTIQUE_DIR . '/inc/components.php';
require SKIRTTIQUE_DIR . '/inc/blocks.php';
require SKIRTTIQUE_DIR . '/inc/assets.php';
require SKIRTTIQUE_DIR . '/inc/performance.php';
require SKIRTTIQUE_DIR . '/inc/woocommerce.php';
