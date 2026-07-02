/**
 * The Drape — scroll reveal.
 *
 * Elements with `.st-drape` open bottom-up like falling fabric, once,
 * when they enter the viewport. Reduced-motion users see content
 * immediately (the CSS also guards this; the check here avoids
 * needless observation work).
 */

export function initDrape(): void {
	const elements = document.querySelectorAll<HTMLElement>( '.st-drape' );
	if ( elements.length === 0 ) {
		return;
	}

	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
		elements.forEach( ( el ) => el.classList.add( 'is-visible' ) );
		return;
	}

	const observer = new IntersectionObserver(
		( entries ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				}
			}
		},
		{ threshold: 0.25 }
	);

	elements.forEach( ( el ) => observer.observe( el ) );
}
