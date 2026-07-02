<?php
/**
 * Recently viewed service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Recently viewed products — cookie-backed ring buffer (12 items),
 * rendered as an editorial rail on PDP and cart.
 *
 * Skeleton (Stage 3). Implementation lands in Stage 7.
 */
final class RecentlyViewed implements ServiceInterface {

	public function register(): void {
		// Stage 7: track on single product view, expose render callback.
	}
}
