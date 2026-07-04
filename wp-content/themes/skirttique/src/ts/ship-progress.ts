/**
 * Delivery-on-the-house progress (Stage 24) — the bag page's server-
 * rendered progress bar, kept live against the block cart's Store API
 * data (quantity changes never reload the page). The threshold rides a
 * data attribute in the active currency; totals come from wc/store/cart
 * in minor units.
 */

export function initShipProgress(): void {
	const box = document.querySelector<HTMLElement>( '[data-st-ship-progress]' );
	const note = box?.querySelector<HTMLElement>( '[data-st-ship-note]' );
	const bar = box?.querySelector<HTMLElement>( '[data-st-ship-bar]' );
	const registry = window.wp?.data;

	if ( ! box || ! note || ! bar || ! registry ) {
		return;
	}

	const threshold = Number( box.dataset.stShipThreshold ?? '0' );
	if ( ! ( threshold > 0 ) ) {
		return;
	}

	let last = -1;

	registry.subscribe( () => {
		const totals = registry.select( 'wc/store/cart' )?.getCartData?.()?.totals;
		if ( ! totals?.total_items ) {
			return;
		}

		const minor = 10 ** ( totals.currency_minor_unit ?? 0 );
		const subtotal = parseInt( totals.total_items, 10 ) / minor;
		if ( Number.isNaN( subtotal ) || subtotal === last ) {
			return;
		}
		last = subtotal;

		const remaining = Math.max( 0, threshold - subtotal );
		const symbol = totals.currency_symbol ?? '';

		note.textContent =
			remaining > 0
				? `${ symbol }${ Math.ceil( remaining ).toLocaleString() } away from delivery on the house.`
				: 'Delivery on the house — you are there.';
		bar.style.width = `${ Math.min( 100, Math.round( ( subtotal / threshold ) * 100 ) ) }%`;
	} );
}
