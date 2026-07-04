<?php
/**
 * Plugin Name: Skirttique Core
 * Plugin URI: https://skirttique.com
 * Description: Commerce functionality for Skirttique — wishlist, recently viewed, quick view, gateway routing, shipping carriers, schema. Theme-independent by design: the theme dresses the store, this plugin runs it.
 * Version: 1.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Author: Skirttique
 * License: Proprietary
 * Text Domain: skirttique-core
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'SKIRTTIQUE_CORE_VERSION', '1.1.0' );
define( 'SKIRTTIQUE_CORE_FILE', __FILE__ );
define( 'SKIRTTIQUE_CORE_DIR', __DIR__ );

/**
 * PSR-4 autoloader for the Skirttique\Core namespace → src/.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Skirttique\\Core\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$path     = SKIRTTIQUE_CORE_DIR . '/src/' . $relative . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

// Declare WooCommerce feature compatibility (HPOS) before Woo initialises.
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				SKIRTTIQUE_CORE_FILE,
				true
			);
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		\Skirttique\Core\Plugin::instance()->boot();
	}
);
