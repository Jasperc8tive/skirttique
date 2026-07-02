<?php
/**
 * Quick view / quick add service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Quick view (dialog with gallery + variations) and quick add
 * (size-select overlay on product cards), both via nonce-verified
 * AJAX; fully keyboard-accessible.
 *
 * Skeleton (Stage 3). Implementation lands in Stages 6–7.
 */
final class QuickView implements ServiceInterface {

	public function register(): void {
		// Stage 6/7: AJAX endpoints, dialog template, focus trap.
	}
}
