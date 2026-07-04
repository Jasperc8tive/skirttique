<?php
/**
 * Legacy paths — permanent redirects for retired URLs.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 25: /delivery-returns/ split into /shipping/ and /returns/.
 * WordPress's own old-slug redirect does not cover pages (it keys on
 * the `name` query var; page requests parse as `pagename`), so retired
 * page URLs are mapped here explicitly — in the plugin, because a URL's
 * permanence must survive a theme change.
 */
final class LegacyPaths implements ServiceInterface {

	/** @var array<string, string> Retired path (no slashes) → destination path. */
	private const MAP = array(
		'delivery-returns' => '/shipping/',
	);

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ) );
	}

	/**
	 * 301 a retired path to its successor. Only fires on 404s, so a
	 * recreated page at the old slug immediately wins over the map.
	 */
	public function redirect(): void {
		if ( ! is_404() ) {
			return;
		}

		$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );

		if ( isset( self::MAP[ $path ] ) ) {
			wp_safe_redirect( home_url( self::MAP[ $path ] ), 301 );
			exit;
		}
	}
}
