<?php
/**
 * Plugin service container.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core;

use Skirttique\Core\Contracts\ServiceInterface;
use Skirttique\Core\Payments\GatewayRouter;
use Skirttique\Core\Services\CartAjax;
use Skirttique\Core\Services\CollectionMeta;
use Skirttique\Core\Services\ContentTypes;
use Skirttique\Core\Services\Currency;
use Skirttique\Core\Services\HouseContent;
use Skirttique\Core\Services\Market;
use Skirttique\Core\Services\Newsletter;
use Skirttique\Core\Services\QuickView;
use Skirttique\Core\Services\RecentlyViewed;
use Skirttique\Core\Services\Wishlist;

/**
 * Boots every registered service. Services are small, single-purpose
 * classes; each implements ServiceInterface and wires its own hooks.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	/** @var list<class-string<ServiceInterface>> */
	private const SERVICES = array(
		Market::class,
		Currency::class,
		ContentTypes::class,
		CollectionMeta::class,
		HouseContent::class,
		Newsletter::class,
		CartAjax::class,
		Wishlist::class,
		RecentlyViewed::class,
		QuickView::class,
		GatewayRouter::class,
	);

	private function __construct() {}

	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	/**
	 * Instantiate and register all services.
	 */
	public function boot(): void {
		foreach ( self::SERVICES as $service ) {
			( new $service() )->register();
		}
	}
}
