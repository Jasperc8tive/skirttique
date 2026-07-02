/**
 * Skirttique theme entry.
 *
 * One bundle: base styles + the two system behaviours every page uses.
 * Feature-specific scripts (mini-cart, quick view, filtering) ship with
 * their own stages and load only where needed.
 */

import './scss/main.scss';

import { initAnnouncement } from './ts/announcement';
import { initDrape } from './ts/drape';
import { initDrawers } from './ts/drawer';
import { initHeader } from './ts/header';
import { initHemlines } from './ts/hemline';
import { initMarket } from './ts/market';
import { initPopovers } from './ts/popover';

const boot = (): void => {
	initHeader();
	initAnnouncement();
	initPopovers();
	initDrawers();
	initMarket();
	initDrape();
	initHemlines();
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}
