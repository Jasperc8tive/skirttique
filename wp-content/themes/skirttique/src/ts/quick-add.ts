/**
 * Quick add — the card's "Add to bag" for simple products.
 *
 * POSTs to the plugin's cart endpoint through the shared plumbing in
 * cart.ts (fragments applied, bag drawer opened). Variable products
 * never get this button — their card action opens the quick view.
 */

import { postCart } from './cart';

async function addToCart( button: HTMLButtonElement ): Promise<void> {
	const productId = button.dataset.stQuickAdd;
	if ( ! productId || button.getAttribute( 'aria-busy' ) === 'true' ) {
		return;
	}

	button.setAttribute( 'aria-busy', 'true' );

	try {
		await postCart(
			'skirttique_add_to_cart',
			new URLSearchParams( { product_id: productId, quantity: '1' } )
		);
	} catch {
		// Network failure: the shopper can retry.
	} finally {
		button.removeAttribute( 'aria-busy' );
	}
}

export function initQuickAdd(): void {
	document.addEventListener( 'click', ( event ) => {
		const button = ( event.target as HTMLElement ).closest<HTMLButtonElement>( '[data-st-quick-add]' );
		if ( button ) {
			void addToCart( button );
		}
	} );

	// Sort select — submit on change (a <noscript> Apply button covers
	// the no-JS path).
	document.querySelectorAll<HTMLSelectElement>( 'select[data-st-auto-submit]' ).forEach( ( select ) => {
		select.addEventListener( 'change', () => select.form?.submit() );
	} );
}
