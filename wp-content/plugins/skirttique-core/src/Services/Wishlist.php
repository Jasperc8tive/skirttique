<?php
/**
 * Wishlist service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Wishlist — guests in a cookie, customers in user meta, merged on login.
 *
 * Skeleton (Stage 3). Implementation lands in Stage 7 (Product Pages):
 * REST endpoints (nonce-verified), heart toggle on cards and PDP,
 * account wishlist view.
 */
final class Wishlist implements ServiceInterface {

	public function register(): void {
		// Stage 7: register REST routes, enqueue toggle script, render account tab.
	}
}
