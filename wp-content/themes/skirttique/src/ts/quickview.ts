/**
 * Quick view — fetches a product fragment into the modal drawer and
 * wires the injected purchase form. Trigger: any `[data-st-quickview]`
 * (card "Quick view" ghost button, or "Choose size" on variable cards).
 */

import { initHemlines } from './hemline';
import { initPurchaseScopes } from './purchase';
import { refreshWishlistMarks } from './wishlist';

interface QuickViewResponse {
	error?: boolean;
	html?: string;
}

async function open( productId: string ): Promise<void> {
	const dialog = document.getElementById( 'st-drawer-quickview' );
	const body = dialog?.querySelector<HTMLElement>( '[data-st-quickview-body]' );

	if ( ! ( dialog instanceof HTMLDialogElement ) || ! body ) {
		return;
	}

	body.innerHTML = '';
	body.setAttribute( 'aria-busy', 'true' );
	if ( ! dialog.open ) {
		dialog.showModal();
	}

	try {
		const response = await fetch(
			`/?wc-ajax=skirttique_quickview&id=${ encodeURIComponent( productId ) }`,
			{ credentials: 'same-origin' }
		);
		const data = ( await response.json() ) as QuickViewResponse;

		if ( ! data.html ) {
			dialog.close();
			return;
		}

		body.innerHTML = data.html;
		initPurchaseScopes( body );
		initHemlines( body );
		refreshWishlistMarks( body );
	} catch {
		dialog.close();
	} finally {
		body.removeAttribute( 'aria-busy' );
	}
}

export function initQuickView(): void {
	document.addEventListener( 'click', ( event ) => {
		const trigger = ( event.target as HTMLElement ).closest<HTMLElement>( '[data-st-quickview]' );
		if ( trigger?.dataset.stQuickview ) {
			event.preventDefault();
			void open( trigger.dataset.stQuickview );
		}
	} );
}
