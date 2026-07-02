<?php
/**
 * Service contract.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Contracts;

/**
 * A service is a self-contained feature that wires its own hooks.
 */
interface ServiceInterface {

	/**
	 * Attach hooks. Called once on plugins_loaded.
	 */
	public function register(): void;
}
