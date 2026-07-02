<?php
/**
 * Currency conversion + rate resolution.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

namespace Skirttique\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skirttique\Core\Services\Currency;
use Skirttique\Core\Services\Market;

final class CurrencyTest extends TestCase {

	protected function setUp(): void {
		st_test_reset();
	}

	/** The base currency always resolves to a 1.0 multiplier. */
	public function test_base_currency_rate_is_one(): void {
		$this->assertSame( 1.0, Currency::rate( 'NGN' ) );
	}

	/** Placeholder rates are used when no option/filter overrides them. */
	public function test_placeholder_rates_apply_by_default(): void {
		$this->assertSame( 0.00065, Currency::rate( 'USD' ) );
		$this->assertSame( 0.00051, Currency::rate( 'GBP' ) );
	}

	/** Currency codes resolve case-insensitively. */
	public function test_rate_is_case_insensitive(): void {
		$this->assertSame( 0.0118, Currency::rate( 'zar' ) );
	}

	/**
	 * An unknown currency deliberately returns 1.0 — a misconfigured
	 * market fails visibly in NGN, never silently in a wrong number.
	 */
	public function test_unknown_currency_falls_back_to_one(): void {
		$this->assertSame( 1.0, Currency::rate( 'JPY' ) );
	}

	/** A stored rate overrides the placeholder for that currency. */
	public function test_stored_option_overrides_placeholder(): void {
		$GLOBALS['__st_options'][ Currency::RATES_OPTION ] = array( 'USD' => 0.0007 );

		$this->assertSame( 0.0007, Currency::rate( 'USD' ) );
		// Untouched currencies keep their placeholder.
		$this->assertSame( 0.00051, Currency::rate( 'GBP' ) );
	}

	/** The runtime filter wins over both option and placeholder. */
	public function test_filter_overrides_everything(): void {
		$GLOBALS['__st_options'][ Currency::RATES_OPTION ] = array( 'USD' => 0.0007 );
		add_filter(
			Currency::RATES_OPTION,
			static function ( array $rates ): array {
				$rates['USD'] = 0.0009;
				return $rates;
			}
		);

		$this->assertSame( 0.0009, Currency::rate( 'USD' ) );
	}

	/**
	 * Conversion rounds to whole units — ₦68,500 in USD reads as a clean
	 * number, part of the pricing voice (68500 × 0.00065 = 44.525 → 45).
	 */
	public function test_convert_rounds_to_whole_units(): void {
		$_COOKIE[ Market::COOKIE ] = 'US';

		$this->assertSame( 45.0, Currency::convert( 68500.0 ) );
	}

	/** In the base market, conversion is the identity (rate 1.0). */
	public function test_convert_in_base_market_is_identity(): void {
		$_COOKIE[ Market::COOKIE ] = 'NG';

		$this->assertSame( 68500.0, Currency::convert( 68500.0 ) );
	}

	/** Empty and non-numeric prices pass through convert_price() untouched. */
	public function test_convert_price_passes_through_non_numeric(): void {
		$_COOKIE[ Market::COOKIE ] = 'US';
		$currency                  = new Currency();

		$this->assertSame( '', $currency->convert_price( '' ) );
		$this->assertSame( 'abc', $currency->convert_price( 'abc' ) );
	}

	/** A numeric price is converted and returned as a string (WC contract). */
	public function test_convert_price_converts_numeric(): void {
		$_COOKIE[ Market::COOKIE ] = 'GB';

		$currency = new Currency();
		// 68500 × 0.00051 = 34.935 → 35.
		$this->assertSame( '35', $currency->convert_price( '68500' ) );
	}

	/** Admin screens (non-AJAX) keep NGN — catalog editing never sees converted numbers. */
	public function test_admin_context_keeps_base_currency(): void {
		$_COOKIE[ Market::COOKIE ] = 'US';
		$GLOBALS['__st_is_admin']  = true;

		$currency = new Currency();
		$this->assertSame( '68500', $currency->convert_price( '68500' ) );
	}

	/** Admin AJAX (Store API adjacent) DOES convert. */
	public function test_admin_ajax_still_converts(): void {
		$_COOKIE[ Market::COOKIE ]   = 'US';
		$GLOBALS['__st_is_admin']    = true;
		$GLOBALS['__st_doing_ajax']  = true;

		$currency = new Currency();
		$this->assertSame( '45', $currency->convert_price( '68500' ) );
	}

	/** The active WooCommerce currency follows the market. */
	public function test_currency_filter_reflects_market(): void {
		$_COOKIE[ Market::COOKIE ] = 'AE';

		$currency = new Currency();
		$this->assertSame( 'AED', $currency->currency( 'NGN' ) );
	}

	/** Every market prices in whole units (0 decimals). */
	public function test_decimals_are_zero_off_base_market(): void {
		$_COOKIE[ Market::COOKIE ] = 'GB';

		$currency = new Currency();
		$this->assertSame( 0, $currency->decimals( 2 ) );
	}

	/** The variation price cache key carries the active currency. */
	public function test_variation_hash_includes_currency(): void {
		$_COOKIE[ Market::COOKIE ] = 'US';

		$currency = new Currency();
		$hash     = $currency->variation_hash( array( 'base' ) );

		$this->assertContains( 'skirttique_currency_USD', $hash );
	}
}
