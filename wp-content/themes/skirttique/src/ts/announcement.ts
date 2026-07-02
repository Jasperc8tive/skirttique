/**
 * Announcement bar rotator.
 *
 * Cross-fades messages every six seconds; pauses while hovered or
 * focused; static under reduced motion or with a single message.
 */

const INTERVAL_MS = 6000;

export function initAnnouncement(): void {
	const bar = document.querySelector<HTMLElement>( '[data-st-announcement]' );
	if ( ! bar ) {
		return;
	}

	const items = Array.from( bar.querySelectorAll<HTMLElement>( '.st-announcement__item' ) );
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( items.length < 2 || reduced ) {
		return;
	}

	let current = 0;
	let paused = false;

	window.setInterval( () => {
		if ( paused ) {
			return;
		}

		items[ current ]?.classList.remove( 'is-current' );
		current = ( current + 1 ) % items.length;
		items[ current ]?.classList.add( 'is-current' );
	}, INTERVAL_MS );

	bar.addEventListener( 'mouseenter', () => ( paused = true ) );
	bar.addEventListener( 'mouseleave', () => ( paused = false ) );
	bar.addEventListener( 'focusin', () => ( paused = true ) );
	bar.addEventListener( 'focusout', () => ( paused = false ) );
}
