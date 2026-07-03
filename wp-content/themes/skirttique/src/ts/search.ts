/**
 * Instant search (Stage 22) — as-you-type product results in the header
 * search drawer, from the plugin's read-only skirttique_search endpoint.
 * Progressive enhancement over the plain GET form: with no JS the form
 * still submits to the full search page.
 */

const MIN_CHARS = 2;
const DEBOUNCE_MS = 300;

interface SearchPayload {
	cards?: string[];
	total?: number;
	url?: string;
}

export function initInstantSearch(): void {
	const form = document.querySelector<HTMLFormElement>( '[data-st-instant-search]' );
	const field = form?.querySelector<HTMLInputElement>( 'input[type="search"]' );
	const status = document.querySelector<HTMLElement>( '[data-st-search-status]' );
	const results = document.querySelector<HTMLElement>( '[data-st-search-results]' );
	const all = document.querySelector<HTMLElement>( '[data-st-search-all]' );
	const allLink = all?.querySelector<HTMLAnchorElement>( 'a' );

	if ( ! form || ! field || ! status || ! results || ! all || ! allLink ) {
		return;
	}

	let timer = 0;
	let inflight: AbortController | null = null;

	const clear = (): void => {
		results.hidden = true;
		results.innerHTML = '';
		all.hidden = true;
		status.textContent = '';
	};

	const render = ( term: string, data: SearchPayload ): void => {
		const cards = data.cards ?? [];
		const total = data.total ?? 0;

		if ( cards.length === 0 ) {
			clear();
			status.textContent = `No pieces match “${ term }” — try another word.`;
			return;
		}

		results.innerHTML = cards.join( '' );
		results.hidden = false;

		if ( data.url && total > cards.length ) {
			allLink.href = data.url;
			allLink.textContent = `View all ${ total } results`;
			all.hidden = false;
		} else {
			all.hidden = true;
		}

		status.textContent =
			total === 1 ? 'One piece found.' : `${ total } pieces found.`;
	};

	const lookUp = async ( term: string ): Promise<void> => {
		inflight?.abort();
		inflight = new AbortController();

		try {
			const response = await fetch(
				`/?wc-ajax=skirttique_search&term=${ encodeURIComponent( term ) }`,
				{ credentials: 'same-origin', signal: inflight.signal }
			);
			render( term, ( await response.json() ) as SearchPayload );
		} catch {
			// Aborted or failed — the plain form submit still works.
		}
	};

	field.addEventListener( 'input', () => {
		window.clearTimeout( timer );

		const term = field.value.trim();
		if ( term.length < MIN_CHARS ) {
			inflight?.abort();
			clear();
			return;
		}

		timer = window.setTimeout( () => void lookUp( term ), DEBOUNCE_MS );
	} );
}
