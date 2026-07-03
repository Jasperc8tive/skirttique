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
import { initAmbientVideo, initZoom } from './ts/media';
import { initParallax } from './ts/parallax';
import { initPopovers } from './ts/popover';
import { initPurchaseScopes } from './ts/purchase';
import { initQuickAdd } from './ts/quick-add';
import { initQuickView } from './ts/quickview';
import { initRecentlyViewed } from './ts/recently-viewed';
import { initRotators } from './ts/rotator';
import { initInstantSearch } from './ts/search';
import { initSliders } from './ts/slider';
import { initStickyBuy } from './ts/sticky-atc';
import { initTransitions } from './ts/transitions';
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
	initInstantSearch();
	initStickyBuy();
	initRecentlyViewed();
	initSliders();
	initParallax();
	initZoom();
	initAmbientVideo();
	initTransitions();
	initDrape();
	initHemlines();
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}
