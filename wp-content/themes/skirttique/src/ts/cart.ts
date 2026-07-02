/**
 * Cart plumbing shared by quick add (cards) and the purchase form
 * (PDP + quick view): fragment application and the bag-drawer
 * confirmation.
 */

import { initHemlines } from './hemline';

export type CartFragments = Record<string, string>;

interface WpDataRegistry {
	select: ( store: string ) => { getCartData?: () => { itemsCount?: number } } | undefined;
	subscribe: ( listener: () => void ) => void;
}

declare global {
	interface Window {
		wp?: { data?: WpDataRegistry };
	}
}

/**
 * The block cart/checkout mutate the cart through the Store API, which
 * never fires WooCommerce's legacy fragment refresh — so on those pages
 * the header count bubble would sit stale until the next navigation.
 * Where wp.data is present, mirror the store's item count onto it.
 */
export function initCartSync(): void {
	const registry = window.wp?.data;
	if ( ! registry ) {
		return;
	}

	registry.subscribe( () => {
		const count = registry.select( 'wc/store/cart' )?.getCartData?.()?.itemsCount;
		const bubble = document.querySelector<HTMLElement>( '[data-st-cart-count]' );

		if ( typeof count === 'number' && bubble && bubble.textContent !== String( count ) ) {
			bubble.textContent = String( count );
			bubble.classList.toggle( 'is-empty', count === 0 );
		}

		// React re-renders block content after our boot pass; re-enhance
		// any hemline links it brought in (idempotent per link).
		initHemlines();
	} );
}

export interface CartResponse {
	error?: boolean;
	product_url?: string;
	fragments?: CartFragments;
}

export function applyFragments( fragments: CartFragments ): void {
	Object.entries( fragments ).forEach( ( [ selector, html ] ) => {
		document.querySelectorAll( selector ).forEach( ( el ) => {
			el.outerHTML = html;
		} );
	} );
}

export function openBag(): void {
	const bag = document.getElementById( 'st-drawer-bag' );
	if ( bag instanceof HTMLDialogElement && ! bag.open ) {
		bag.showModal();
	}
}

/**
 * POST to a wc-ajax cart endpoint and settle the standard response:
 * fragments applied and the bag opened on success; on a refusal with a
 * product URL, navigate there so WooCommerce's notices can explain.
 *
 * @return true when the item was added.
 */
export async function postCart( endpoint: string, body: URLSearchParams ): Promise<boolean> {
	const response = await fetch( `/?wc-ajax=${ endpoint }`, {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body,
	} );
	const data = ( await response.json() ) as CartResponse;

	if ( data.error ) {
		if ( data.product_url ) {
			window.location.assign( data.product_url );
		}
		return false;
	}

	if ( data.fragments ) {
		applyFragments( data.fragments );
	}

	openBag();
	return true;
}
