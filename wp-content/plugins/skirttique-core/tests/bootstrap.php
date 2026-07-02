<?php
/**
 * Standalone unit-test bootstrap for skirttique-core.
 *
 * These are true unit tests: the money-critical logic (currency, market,
 * gateway routing, settings sanitizers) is exercised in isolation with
 * lightweight stand-ins for the handful of WordPress functions the code
 * touches. No database, no WordPress, no Docker — fast and deterministic.
 *
 * The stubs mirror WordPress semantics only as far as the assertions
 * require; they are NOT a WordPress emulator. Integration behaviour
 * (real hooks, real WC) is proven separately against the running site.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

// PSR-4 autoloader for the plugin's Skirttique\Core namespace.
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Skirttique\\Core\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$path     = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_file( $path ) ) {
			require $path;
		}
	}
);

/**
 * Reset all stub state. Called from each test's setUp so cases never leak.
 */
function st_test_reset(): void {
	$GLOBALS['__st_options']     = array();
	$GLOBALS['__st_filters']     = array();
	$GLOBALS['__st_is_admin']    = false;
	$GLOBALS['__st_doing_ajax']  = false;
	$GLOBALS['__st_wc_currency'] = 'NGN';
	$GLOBALS['__st_image_ids']   = array();
	$_COOKIE                     = array();
}

// ---------------------------------------------------------------------
// Minimal hook registry — enough to test the filter code paths.
// ---------------------------------------------------------------------

function add_filter( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['__st_filters'][ $tag ][] = $callback;
	return true;
}

function apply_filters( string $tag, $value, ...$args ) {
	foreach ( $GLOBALS['__st_filters'][ $tag ] ?? array() as $callback ) {
		$value = $callback( $value, ...$args );
	}
	return $value;
}

// ---------------------------------------------------------------------
// Options.
// ---------------------------------------------------------------------

function get_option( string $name, $default = false ) {
	return $GLOBALS['__st_options'][ $name ] ?? $default;
}

// ---------------------------------------------------------------------
// Request/scope flags.
// ---------------------------------------------------------------------

function is_admin(): bool {
	return (bool) ( $GLOBALS['__st_is_admin'] ?? false );
}

function wp_doing_ajax(): bool {
	return (bool) ( $GLOBALS['__st_doing_ajax'] ?? false );
}

function get_woocommerce_currency(): string {
	return (string) ( $GLOBALS['__st_wc_currency'] ?? 'NGN' );
}

// ---------------------------------------------------------------------
// Sanitizers / escapers (semantics only as far as assertions need).
// ---------------------------------------------------------------------

function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '';
}

function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function absint( $value ): int {
	return abs( (int) $value );
}

function esc_url_raw( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}
	// Reject unsafe protocols the way esc_url does for the cases we test.
	if ( preg_match( '/^\s*javascript:/i', $url ) ) {
		return '';
	}
	return $url;
}

function wp_attachment_is_image( int $id ): bool {
	return in_array( $id, $GLOBALS['__st_image_ids'] ?? array(), true );
}

function sanitize_textarea_field( string $value ): string {
	// Strip tags, drop control chars except newline/tab, trim — newlines kept.
	$value = wp_strip_all_tags( $value );
	$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value ) ?? '';
	return trim( $value );
}

function sanitize_text_field( string $value ): string {
	$value = wp_strip_all_tags( $value );
	$value = preg_replace( '/[\r\n\t ]+/', ' ', $value ) ?? '';
	return trim( $value );
}

function wp_strip_all_tags( string $value ): string {
	$value = preg_replace( '/<(script|style)[^>]*?>.*?<\/\1>/is', '', $value ) ?? $value;
	return trim( strip_tags( $value ) );
}
