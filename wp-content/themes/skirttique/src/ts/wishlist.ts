/**
 * Wishlist — guests in localStorage, customers in user meta via the
 * plugin's nonce-verified endpoints; a guest list merges up on the
 * first logged-in page view. Toggles are event-delegated so injected
 * content (quick view) works without re-wiring.
 */

interface StConfig {
	loggedIn?: boolean;
	wishlistNonce?: string;
}

declare global {
	interface Window {
		stConfig?: StConfig;
	}
}

const STORAGE_KEY = 'skirttique_wishlist';

let ids: number[] = [];

const config = (): StConfig => window.stConfig ?? {};

function readLocal(): number[] {
	try {
		const raw = window.localStorage.getItem( STORAGE_KEY );
		const parsed: unknown = raw ? JSON.parse( raw ) : [];
		return Array.isArray( parsed ) ? parsed.map( Number ).filter( Number.isInteger ) : [];
	} catch {
		return [];
	}
}

function writeLocal( list: number[] ): void {
	try {
		window.localStorage.setItem( STORAGE_KEY, JSON.stringify( list ) );
	} catch {
		// Storage unavailable (private mode) — the session just won't persist.
	}
}

async function post( action: string, params: Record<string, string | number[] > ): Promise<unknown> {
	const body = new URLSearchParams();
	body.set( 'nonce', config().wishlistNonce ?? '' );
	Object.entries( params ).forEach( ( [ key, value ] ) => {
		if ( Array.isArray( value ) ) {
			value.forEach( ( item ) => body.append( `${ key }[]`, String( item ) ) );
		} else {
			body.set( key, value );
		}
	} );

	const response = await fetch( `/?wc-ajax=${ action }`, {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body,
	} );
	return response.json();
}

/**
 * Reflect the current list onto every toggle under `root`.
 */
export function refreshWishlistMarks( root: ParentNode = document ): void {
	root.querySelectorAll<HTMLElement>( '[data-st-wishlist-toggle]' ).forEach( ( toggle ) => {
		const id = Number( toggle.dataset.stWishlistToggle );
		const saved = ids.includes( id );
		toggle.setAttribute( 'aria-pressed', String( saved ) );

		const label = toggle.querySelector( '[data-st-wishlist-label]' );
		if ( label ) {
			label.textContent = saved ? 'Saved' : 'Save';
		}
	} );
}

async function toggle( id: number ): Promise<void> {
	const saved = ids.includes( id );
	ids = saved ? ids.filter( ( item ) => item !== id ) : [ id, ...ids ];
	refreshWishlistMarks();

	if ( config().loggedIn ) {
		try {
			const data = ( await post( 'skirttique_wishlist_toggle', { product_id: String( id ) } ) ) as {
				ids?: number[];
			};
			if ( data.ids ) {
				ids = data.ids;
			}
		} catch {
			// Revert the optimistic flip on failure.
			ids = saved ? [ id, ...ids ] : ids.filter( ( item ) => item !== id );
		}
		refreshWishlistMarks();
	} else {
		writeLocal( ids );
	}
}

async function renderSavedPage(): Promise<void> {
	const grid = document.querySelector<HTMLElement>( '[data-st-saved-grid]' );
	const empty = document.querySelector<HTMLElement>( '[data-st-saved-empty]' );

	if ( ! grid || ids.length === 0 ) {
		return;
	}

	try {
		const response = await fetch(
			`/?wc-ajax=skirttique_product_cards&ids=${ ids.join( ',' ) }`,
			{ credentials: 'same-origin' }
		);
		const data = ( await response.json() ) as { cards?: Record<string, string> };
		const cards = data.cards ?? {};
		const html = ids.map( ( id ) => cards[ String( id ) ] ?? '' ).join( '' );

		if ( html ) {
			grid.innerHTML = html;
			grid.hidden = false;
			if ( empty ) {
				empty.hidden = true;
			}
		}
	} catch {
		// Endpoint unreachable — the empty state stays, shop link intact.
	}
}

export function initWishlist(): void {
	document.addEventListener( 'click', ( event ) => {
		const trigger = ( event.target as HTMLElement ).closest<HTMLElement>( '[data-st-wishlist-toggle]' );
		if ( trigger ) {
			void toggle( Number( trigger.dataset.stWishlistToggle ) );
		}
	} );

	const boot = async (): Promise<void> => {
		if ( config().loggedIn ) {
			const local = readLocal();

			try {
				if ( local.length > 0 ) {
					// First logged-in view after guest browsing: merge up, then
					// the account list is the single source of truth.
					const merged = ( await post( 'skirttique_wishlist_merge', { ids: local } ) ) as {
						ids?: number[];
					};
					ids = merged.ids ?? [];
					writeLocal( [] );
				} else {
					const data = ( await fetch( '/?wc-ajax=skirttique_wishlist_get', { credentials: 'same-origin' } ).then(
						( r ) => r.json()
					) ) as { ids?: number[] };
					ids = data.ids ?? [];
				}
			} catch {
				ids = [];
			}
		} else {
			ids = readLocal();
		}

		refreshWishlistMarks();
		void renderSavedPage();
	};

	void boot();
}
