<?php
/**
 * Payment gateway router.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Payments;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Routes checkout to the right gateway for the customer's currency —
 * the heart of the full multi-currency model approved in Stage 1:
 *
 *   NGN → Paystack · everything else → Stripe (per-currency map,
 *   extensible to Flutterwave and future gateways without code changes
 *   elsewhere).
 *
 * Skeleton (Stage 3). Implementation lands in Stage 9 (Checkout):
 * filters woocommerce_available_payment_gateways by active currency.
 */
final class GatewayRouter implements ServiceInterface {

	/**
	 * Currency → gateway id map. Filterable so new gateways are additive.
	 *
	 * @var array<string, string>
	 */
	private const CURRENCY_GATEWAYS = array(
		'NGN' => 'paystack',
		'ZAR' => 'stripe',
		'GBP' => 'stripe',
		'USD' => 'stripe',
		'AED' => 'stripe',
	);

	public function register(): void {
		// Stage 9: hook woocommerce_available_payment_gateways.
	}

	/**
	 * Gateway id for a currency (defaults to Stripe for unknown codes).
	 */
	public function gateway_for( string $currency ): string {
		$map = apply_filters( 'skirttique_currency_gateways', self::CURRENCY_GATEWAYS );

		return $map[ strtoupper( $currency ) ] ?? 'stripe';
	}
}
