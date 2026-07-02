<?php
/**
 * Market cookie validation and fallback.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

namespace Skirttique\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skirttique\Core\Services\Market;

final class MarketTest extends TestCase {

	protected function setUp(): void {
		st_test_reset();
	}

	/** No cookie → the default market (Nigeria). */
	public function test_defaults_to_nigeria_without_cookie(): void {
		$this->assertSame( 'NG', Market::current() );
	}

	/** A valid cookie is honoured. */
	public function test_valid_cookie_is_used(): void {
		$_COOKIE[ Market::COOKIE ] = 'GB';
		$this->assertSame( 'GB', Market::current() );
	}

	/** Lower-case cookies are normalised to the upper-case map keys. */
	public function test_cookie_is_normalised_to_upper_case(): void {
		$_COOKIE[ Market::COOKIE ] = 'us';
		$this->assertSame( 'US', Market::current() );
	}

	/** An unknown code falls back to the default rather than trusting input. */
	public function test_unknown_code_falls_back_to_default(): void {
		$_COOKIE[ Market::COOKIE ] = 'FR';
		$this->assertSame( 'NG', Market::current() );
	}

	/** A junk/injection cookie value cannot select a market. */
	public function test_junk_cookie_falls_back_to_default(): void {
		$_COOKIE[ Market::COOKIE ] = '"><script>';
		$this->assertSame( 'NG', Market::current() );
	}

	/** current_market() returns the full definition for the active market. */
	public function test_current_market_returns_definition(): void {
		$_COOKIE[ Market::COOKIE ] = 'AE';

		$market = Market::current_market();
		$this->assertSame( 'AED', $market['currency'] );
		$this->assertSame( 'United Arab Emirates', $market['label'] );
	}

	/** All five markets are present with the expected currencies. */
	public function test_all_markets_present(): void {
		$all = Market::all();

		$this->assertSame(
			array( 'NG', 'ZA', 'GB', 'US', 'AE' ),
			array_keys( $all )
		);
		$this->assertSame( 'NGN', $all['NG']['currency'] );
		$this->assertSame( 'ZAR', $all['ZA']['currency'] );
	}

	/** The markets list is filterable (additive extension point). */
	public function test_markets_are_filterable(): void {
		add_filter(
			'skirttique_markets',
			static function ( array $markets ): array {
				$markets['KE'] = array( 'label' => 'Kenya', 'currency' => 'KES', 'symbol' => 'KSh' );
				return $markets;
			}
		);

		$_COOKIE[ Market::COOKIE ] = 'KE';
		$this->assertSame( 'KE', Market::current() );
		$this->assertSame( 'KES', Market::current_market()['currency'] );
	}
}
