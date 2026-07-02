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
 * Routing is deliberately soft: when the mapped gateway is enabled and
 * configured, it becomes the ONLY offer at checkout; when it is absent
 * or unconfigured (no API keys yet), the available list passes through
 * untouched, so checkout never dead-ends on a half-configured store.
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
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'route' ) );
	}

	/**
	 * Narrow the available gateways to the one mapped for the active
	 * currency. Admin screens (order editing, settings) are left alone.
	 *
	 * @param array<string, \WC_Payment_Gateway> $gateways Available gateways.
	 * @return array<string, \WC_Payment_Gateway>
	 */
	public function route( array $gateways ): array {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		$target = $this->gateway_for( get_woocommerce_currency() );

		return isset( $gateways[ $target ] )
			? array( $target => $gateways[ $target ] )
			: $gateways;
	}

	/**
	 * Gateway id for a currency (defaults to Stripe for unknown codes).
	 */
	public function gateway_for( string $currency ): string {
		$map = apply_filters( 'skirttique_currency_gateways', self::CURRENCY_GATEWAYS );

		return $map[ strtoupper( $currency ) ] ?? 'stripe';
	}
}
