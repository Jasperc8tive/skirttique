/**
 * Skirttique theme entry.
 *
 * One bundle: base styles + system behaviours. Modules whose markup
 * only exists on some pages (purchase form, recently-viewed rail) find
 * nothing and cost nothing elsewhere — everything is data-attribute
 * driven, so there is no per-template enqueue dance.
 */

import './scss/main.scss';

import { initCartSync } from './ts/cart';
import { initDrape } from './ts/drape';
import { initDrawers } from './ts/drawer';
import { initHeader } from './ts/header';
import { initHemlines } from './ts/hemline';
import { initMarket } from './ts/market';
import { initPopovers } from './ts/popover';
import { initPurchaseScopes } from './ts/purchase';
import { initQuickAdd } from './ts/quick-add';
import { initQuickView } from './ts/quickview';
import { initRecentlyViewed } from './ts/recently-viewed';
import { initRotators } from './ts/rotator';
import { initWishlist } from './ts/wishlist';

const boot = (): void => {
	initHeader();
	initRotators();
	initPopovers();
	initDrawers();
	initMarket();
	initQuickAdd();
	initCartSync();
	initQuickView();
	initWishlist();
	initPurchaseScopes();
	initRecentlyViewed();
	initDrape();
	initHemlines();
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}
