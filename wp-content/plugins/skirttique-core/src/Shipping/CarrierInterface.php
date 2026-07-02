<?php
/**
 * Shipping carrier contract.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Shipping;

/**
 * A shipping carrier integration (GIG Logistics, DHL, FedEx, UPS, …).
 *
 * Implementations register as WooCommerce shipping methods and may
 * provide live rates and tracking. New carriers are additive: implement
 * this interface, register the class — nothing else changes (Stage 1
 * architecture decision).
 */
interface CarrierInterface {

	/**
	 * Unique carrier id, e.g. 'gig-logistics'.
	 */
	public function id(): string;

	/**
	 * Human-readable name shown at checkout and in admin.
	 */
	public function label(): string;

	/**
	 * ISO 3166-1 alpha-2 country codes this carrier serves.
	 *
	 * @return list<string>
	 */
	public function countries(): array;
}
