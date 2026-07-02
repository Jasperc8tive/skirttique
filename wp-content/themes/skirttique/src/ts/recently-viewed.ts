/**
 * Recently viewed — a localStorage ring buffer (8 ids, newest first),
 * written on every PDP visit and rendered as a rail on the PDP through
 * the plugin's product-cards endpoint. Client-side only: no server
 * tracking, nothing to consent-gate.
 */

const STORAGE_KEY = 'skirttique_recent';
const MAX_ITEMS = 8;

function read(): number[] {
	try {
		const raw = window.localStorage.getItem( STORAGE_KEY );
		const parsed: unknown = raw ? JSON.parse( raw ) : [];
		return Array.isArray( parsed ) ? parsed.map( Number ).filter( Number.isInteger ) : [];
	} catch {
		return [];
	}
}

export function initRecentlyViewed(): void {
	const marker = document.querySelector<HTMLElement>( '[data-st-viewed]' );
	const currentId = marker ? Number( marker.dataset.stViewed ) : 0;
	const previous = read();

	// Render before recording, so the rail shows where you have been —
	// not the page you are already on.
	const railIds = previous.filter( ( id ) => id !== currentId );
	const rail = document.querySelector<HTMLElement>( '[data-st-recent]' );
	const grid = document.querySelector<HTMLElement>( '[data-st-recent-grid]' );

	if ( rail && grid && railIds.length > 0 ) {
		void ( async () => {
			try {
				const response = await fetch(
					`/?wc-ajax=skirttique_product_cards&ids=${ railIds.join( ',' ) }`,
					{ credentials: 'same-origin' }
				);
				const data = ( await response.json() ) as { cards?: Record<string, string> };
				const cards = data.cards ?? {};
				const html = railIds.map( ( id ) => cards[ String( id ) ] ?? '' ).join( '' );

				if ( html ) {
					grid.innerHTML = html;
					rail.hidden = false;
				}
			} catch {
				// Rail simply stays hidden.
			}
		} )();
	}

	if ( currentId > 0 ) {
		try {
			window.localStorage.setItem(
				STORAGE_KEY,
				JSON.stringify( [ currentId, ...previous.filter( ( id ) => id !== currentId ) ].slice( 0, MAX_ITEMS ) )
			);
		} catch {
			// Storage unavailable — feature quietly absent.
		}
	}
}
