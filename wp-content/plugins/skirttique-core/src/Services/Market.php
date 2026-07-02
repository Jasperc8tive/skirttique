<?php
/**
 * Market service — the customer's shopping market.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Source of truth for the five Skirttique markets and the visitor's
 * current choice (persisted in a cookie by the header selector).
 *
 * Stage 4 ships the map and cookie reading. Stage 9 builds on it:
 * currency conversion, price display, and gateway routing all key off
 * Market::current().
 */
final class Market implements ServiceInterface {

	public const COOKIE = 'skirttique_market';

	/**
	 * Market definitions, keyed by ISO 3166-1 alpha-2 country code.
	 *
	 * @var array<string, array{label: string, currency: string, symbol: string}>
	 */
	private const MARKETS = array(
		'NG' => array(
			'label'    => 'Nigeria',
			'currency' => 'NGN',
			'symbol'   => '₦',
		),
		'ZA' => array(
			'label'    => 'South Africa',
			'currency' => 'ZAR',
			'symbol'   => 'R',
		),
		'GB' => array(
			'label'    => 'United Kingdom',
			'currency' => 'GBP',
			'symbol'   => '£',
		),
		'US' => array(
			'label'    => 'United States',
			'currency' => 'USD',
			'symbol'   => '$',
		),
		'AE' => array(
			'label'    => 'United Arab Emirates',
			'currency' => 'AED',
			'symbol'   => 'د.إ',
		),
	);

	public const DEFAULT_MARKET = 'NG';

	public function register(): void {
		// Stage 9: hook currency filters and price conversion off current().
	}

	/**
	 * All markets (filterable — additions are additive, never code edits).
	 *
	 * @return array<string, array{label: string, currency: string, symbol: string}>
	 */
	public static function all(): array {
		/**
		 * Filter the available markets.
		 *
		 * @param array<string, array{label: string, currency: string, symbol: string}> $markets Markets keyed by country code.
		 */
		return apply_filters( 'skirttique_markets', self::MARKETS );
	}

	/**
	 * The visitor's current market code, validated against the map.
	 */
	public static function current(): string {
		$cookie = isset( $_COOKIE[ self::COOKIE ] )
			? strtoupper( sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) )
			: '';

		return array_key_exists( $cookie, self::all() ) ? $cookie : self::DEFAULT_MARKET;
	}

	/**
	 * Definition for the visitor's current market.
	 *
	 * @return array{label: string, currency: string, symbol: string}
	 */
	public static function current_market(): array {
		return self::all()[ self::current() ];
	}
}
