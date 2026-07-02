<?php
/**
 * Gateway routing per currency — the multi-currency settlement contract.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

namespace Skirttique\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skirttique\Core\Payments\GatewayRouter;

final class GatewayRouterTest extends TestCase {

	protected function setUp(): void {
		st_test_reset();
	}

	/** Naira settles through Paystack. */
	public function test_ngn_routes_to_paystack(): void {
		$router = new GatewayRouter();
		$this->assertSame( 'paystack', $router->gateway_for( 'NGN' ) );
	}

	/** Every international currency settles through Stripe. */
	public function test_international_currencies_route_to_stripe(): void {
		$router = new GatewayRouter();

		foreach ( array( 'USD', 'GBP', 'ZAR', 'AED' ) as $currency ) {
			$this->assertSame( 'stripe', $router->gateway_for( $currency ), "$currency should use Stripe" );
		}
	}

	/** Unknown currencies default to Stripe (the international rail). */
	public function test_unknown_currency_defaults_to_stripe(): void {
		$router = new GatewayRouter();
		$this->assertSame( 'stripe', $router->gateway_for( 'CAD' ) );
	}

	/** Routing is case-insensitive. */
	public function test_gateway_lookup_is_case_insensitive(): void {
		$router = new GatewayRouter();
		$this->assertSame( 'paystack', $router->gateway_for( 'ngn' ) );
	}

	/** The map is filterable so new gateways are additive (e.g. Flutterwave). */
	public function test_gateway_map_is_filterable(): void {
		add_filter(
			'skirttique_currency_gateways',
			static function ( array $map ): array {
				$map['NGN'] = 'flutterwave';
				return $map;
			}
		);

		$router = new GatewayRouter();
		$this->assertSame( 'flutterwave', $router->gateway_for( 'NGN' ) );
	}

	/**
	 * When the mapped gateway is available, it becomes the only offer at
	 * checkout.
	 */
	public function test_route_narrows_to_mapped_gateway(): void {
		$GLOBALS['__st_wc_currency'] = 'NGN';

		$gateways = array(
			'paystack' => 'PAYSTACK_OBJ',
			'stripe'   => 'STRIPE_OBJ',
			'cod'      => 'COD_OBJ',
		);

		$router = new GatewayRouter();
		$routed = $router->route( $gateways );

		$this->assertSame( array( 'paystack' => 'PAYSTACK_OBJ' ), $routed );
	}

	/**
	 * When the mapped gateway is NOT configured, the list passes through
	 * untouched — checkout never dead-ends on a half-configured store.
	 */
	public function test_route_passes_through_when_gateway_absent(): void {
		$GLOBALS['__st_wc_currency'] = 'USD'; // maps to stripe...

		$gateways = array( 'paystack' => 'PAYSTACK_OBJ', 'cod' => 'COD_OBJ' ); // ...but stripe isn't enabled.

		$router = new GatewayRouter();
		$this->assertSame( $gateways, $router->route( $gateways ) );
	}

	/** Admin screens (non-AJAX) are left alone — order/settings editing sees all gateways. */
	public function test_route_leaves_admin_untouched(): void {
		$GLOBALS['__st_wc_currency'] = 'NGN';
		$GLOBALS['__st_is_admin']    = true;

		$gateways = array( 'paystack' => 'P', 'stripe' => 'S' );

		$router = new GatewayRouter();
		$this->assertSame( $gateways, $router->route( $gateways ) );
	}
}
