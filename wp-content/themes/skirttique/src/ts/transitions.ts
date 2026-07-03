/**
 * Page transitions — a nectar veil falls before internal navigation and
 * lifts on arrival. Progressive and conservative: only plain left-
 * clicks on same-origin, non-anchor, non-download links; never under
 * reduced motion; the veil never blocks longer than the failsafe.
 */

const VEIL_MS = 450;

function eligible( link: HTMLAnchorElement, event: MouseEvent ): boolean {
	if ( event.defaultPrevented || event.button !== 0 ) {
		return false;
	}
	if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
		return false;
	}
	if ( link.target && link.target !== '_self' ) {
		return false;
	}
	if ( link.hasAttribute( 'download' ) || link.origin !== window.location.origin ) {
		return false;
	}
	// Same-page anchors and pure hash changes stay instant.
	const samePath = link.pathname === window.location.pathname && link.search === window.location.search;
	if ( link.hash && samePath ) {
		return false;
	}

	return true;
}

export function initTransitions(): void {
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}
	// Owner's Experience switch (House Settings).
	if ( window.stConfig?.motion?.transitions === false ) {
		return;
	}

	const veil = document.createElement( 'div' );
	veil.className = 'st-veil';
	veil.setAttribute( 'aria-hidden', 'true' );
	document.body.append( veil );

	// Arrival: start veiled (class set inline in head is not available,
	// so fade from the first frame instead — imperceptible on fast loads).
	// rAF never fires in a background tab, so a timeout failsafe makes
	// sure a tab loaded out of view is never stuck veiled.
	const arrive = (): void => document.documentElement.classList.remove( 'st-arriving' );
	document.documentElement.classList.add( 'st-arriving' );
	window.requestAnimationFrame( () => window.requestAnimationFrame( arrive ) );
	window.setTimeout( arrive, 600 );

	// bfcache restores must never come back veiled.
	window.addEventListener( 'pageshow', ( event ) => {
		if ( event.persisted ) {
			document.documentElement.classList.remove( 'st-leaving', 'st-arriving' );
		}
	} );

	document.addEventListener( 'click', ( event ) => {
		const link = ( event.target as HTMLElement ).closest<HTMLAnchorElement>( 'a[href]' );
		if ( ! link || ! eligible( link, event ) ) {
			return;
		}

		event.preventDefault();
		document.documentElement.classList.add( 'st-leaving' );

		window.setTimeout( () => {
			window.location.assign( link.href );
		}, VEIL_MS );

		// If navigation is blocked (beforeunload prompt, download manager),
		// never leave the page dead behind an opaque veil.
		window.setTimeout( () => {
			document.documentElement.classList.remove( 'st-leaving' );
		}, VEIL_MS + 2500 );
	} );
}
