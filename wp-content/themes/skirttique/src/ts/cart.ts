/**
 * Cart plumbing shared by quick add (cards) and the purchase form
 * (PDP + quick view): fragment application and the bag-drawer
 * confirmation.
 */

export type CartFragments = Record<string, string>;

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
