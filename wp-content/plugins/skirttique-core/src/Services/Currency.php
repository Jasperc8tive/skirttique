<?php
/**
 * Currency service — full multi-currency display and settlement.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * The Stage 1 decision, made real: the customer shops, sees prices,
 * and PAYS in the currency of their chosen market (Market::current()).
 * Catalog prices are authored once, in NGN; this service converts every
 * price read at request time, so the cart, checkout, and the order
 * itself are natively in the market currency. Gateway choice per
 * currency is GatewayRouter's job, not ours.
 *
 * Rates are NGN-based multipliers: converted = NGN price × rate. The
 * shipped defaults are PLACEHOLDERS — set real rates in the
 * `skirttique_currency_rates` option (currency → rate) or via the
 * filter of the same name; a managed rate feed is a later stage's
 * concern. Prices round to whole units in every market (₦68,500 → $45,
 * never $44.53) — clean numbers are part of the register.
 *
 * Scope guards: admin screens keep the NGN base (catalog editing must
 * never see converted numbers); REST (Store API checkout), wc-ajax,
 * and ordinary page views convert. WP-CLI has no market cookie, so it
 * naturally stays NGN.
 */
final class Currency implements ServiceInterface {

	public const BASE_CURRENCY = 'NGN';
	public const RATES_OPTION  = 'skirttique_currency_rates';

	/**
	 * Placeholder NGN → currency multipliers (mid-2026 ballpark).
	 *
	 * @var array<string, float>
	 */
	private const PLACEHOLDER_RATES = array(
		'NGN' => 1.0,
		'USD' => 0.00065,
		'GBP' => 0.00051,
		'ZAR' => 0.0118,
		'AED' => 0.0024,
	);

	public function register(): void {
		add_filter( 'woocommerce_currency', array( $this, 'currency' ) );
		add_filter( 'wc_get_price_decimals', array( $this, 'decimals' ) );

		// Product + variation price reads.
		foreach ( array( 'product', 'product_variation' ) as $type ) {
			foreach ( array( 'price', 'regular_price', 'sale_price' ) as $prop ) {
				add_filter( "woocommerce_{$type}_get_{$prop}", array( $this, 'convert_price' ) );
			}
		}

		// Variable-product range prices are read straight from meta,
		// bypassing the getters above — and their cache must key on the
		// active currency or one market's numbers leak into another's.
		foreach ( array( 'price', 'regular_price', 'sale_price' ) as $prop ) {
			add_filter( "woocommerce_variation_prices_{$prop}", array( $this, 'convert_price' ) );
		}
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_hash' ) );

		// Shipping rates are configured in NGN too.
		add_filter( 'woocommerce_package_rates', array( $this, 'convert_shipping' ) );

		// Price-facet bridge (Stage 22): the shopper types min/max in the
		// MARKET currency, but WooCommerce's price filter compares against
		// the NGN lookup table — normalise the GET params before WC reads
		// them (its posts_clauses hook reads $_GET after pre_get_posts).
		add_action( 'pre_get_posts', array( $this, 'normalize_price_filter' ) );
	}

	/**
	 * Convert typed min/max price params back to the NGN base, once,
	 * for the main storefront query.
	 */
	public function normalize_price_filter( \WP_Query $query ): void {
		static $done = false;

		if ( $done || is_admin() || ! $query->is_main_query() || ! self::active() ) {
			return;
		}
		$done = true;

		$currency = Market::current_market()['currency'];
		if ( self::BASE_CURRENCY === $currency ) {
			return;
		}

		$rate = self::rate( $currency );
		if ( $rate <= 0 ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter params, normalised in place.
		if ( isset( $_GET['min_price'] ) && is_numeric( $_GET['min_price'] ) ) {
			$_GET['min_price'] = (string) floor( (float) $_GET['min_price'] / $rate );
		}
		if ( isset( $_GET['max_price'] ) && is_numeric( $_GET['max_price'] ) ) {
			$_GET['max_price'] = (string) ceil( (float) $_GET['max_price'] / $rate );
		}
		// phpcs:enable
	}

	/**
	 * The active display/settlement currency.
	 */
	public function currency( string $currency ): string {
		if ( ! self::active() ) {
			return $currency;
		}

		return Market::current_market()['currency'];
	}

	/**
	 * Whole units in every market — part of the pricing voice.
	 */
	public function decimals( int $decimals ): int {
		return self::active() ? 0 : $decimals;
	}

	/**
	 * Convert one NGN price into the market currency.
	 *
	 * @param string|int|float $price Raw price (may be '' on variable parents).
	 * @return string|int|float
	 */
	public function convert_price( $price ) {
		if ( ! self::active() || '' === $price || ! is_numeric( $price ) ) {
			return $price;
		}

		return (string) self::convert( (float) $price );
	}

	/**
	 * Convert every candidate shipping rate for the current market.
	 *
	 * @param array<string, \WC_Shipping_Rate> $rates Candidate rates.
	 * @return array<string, \WC_Shipping_Rate>
	 */
	public function convert_shipping( array $rates ): array {
		if ( ! self::active() ) {
			return $rates;
		}

		foreach ( $rates as $rate ) {
			$rate->set_cost( (string) self::convert( (float) $rate->get_cost() ) );

			$taxes = array_map(
				fn ( $tax ): float => self::convert( (float) $tax ),
				$rate->get_taxes()
			);
			$rate->set_taxes( $taxes );
		}

		return $rates;
	}

	/**
	 * Key the variation price cache on the active currency.
	 *
	 * @param array<int, mixed> $hash Hash ingredients.
	 * @return array<int, mixed>
	 */
	public function variation_hash( array $hash ): array {
		$hash[] = 'skirttique_currency_' . ( self::active() ? Market::current_market()['currency'] : self::BASE_CURRENCY );

		return $hash;
	}

	/**
	 * NGN → market-currency conversion, rounded to whole units.
	 */
	public static function convert( float $amount ): float {
		$currency = Market::current_market()['currency'];

		return round( $amount * self::rate( $currency ) );
	}

	/**
	 * The NGN multiplier for a currency (1.0 when unknown, so a
	 * misconfigured market fails loudly-visibly in NGN rather than
	 * silently in a wrong number).
	 */
	public static function rate( string $currency ): float {
		$stored = get_option( self::RATES_OPTION, array() );
		$rates  = array_merge( self::PLACEHOLDER_RATES, is_array( $stored ) ? $stored : array() );

		/**
		 * Filter the NGN-based conversion rates.
		 *
		 * @param array<string, float> $rates Currency → multiplier.
		 */
		$rates = apply_filters( self::RATES_OPTION, $rates );

		return (float) ( $rates[ strtoupper( $currency ) ] ?? 1.0 );
	}

	/**
	 * Whether conversion applies to this request. Admin (minus AJAX)
	 * stays in the NGN base; everything customer-facing converts.
	 */
	private static function active(): bool {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		return self::BASE_CURRENCY !== Market::current_market()['currency'];
	}
}
