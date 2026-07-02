/**
 * Rotator — shared crossfade mechanics for the announcement bar and the
 * press-quote band.
 *
 * A container carrying `data-st-rotate` cycles `.is-current` across its
 * `[data-st-rotate-item]` children; pauses while hovered or focused;
 * static under reduced motion or with a single item. The fade itself is
 * CSS on `.is-current` in each component's stylesheet.
 *
 * `data-st-rotate` may hold a custom interval in milliseconds — quotes
 * need more reading time than a shipping notice.
 *
 * WCAG 2.2.2 (Pause, Stop, Hide): an optional `[data-st-rotate-pause]`
 * button inside the container gives keyboard and screen-reader users an
 * explicit stop. Hover/focus pausing is a courtesy on top, never the
 * compliance mechanism. An explicit pause always wins over hover state.
 */

const DEFAULT_INTERVAL_MS = 6000;

export function initRotators(): void {
	document.querySelectorAll<HTMLElement>( '[data-st-rotate]' ).forEach( ( container ) => {
		const items = Array.from(
			container.querySelectorAll<HTMLElement>( '[data-st-rotate-item]' )
		);
		const pauseButton = container.querySelector<HTMLButtonElement>( '[data-st-rotate-pause]' );
		const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( items.length < 2 || reduced ) {
			// Static bar — the pause control would toggle nothing.
			pauseButton?.setAttribute( 'hidden', '' );
			return;
		}

		const interval = Number.parseInt( container.dataset.stRotate ?? '', 10 ) || DEFAULT_INTERVAL_MS;

		let current = Math.max(
			items.findIndex( ( item ) => item.classList.contains( 'is-current' ) ),
			0
		);
		let hoverPaused = false;
		let userPaused = false;

		window.setInterval( () => {
			if ( hoverPaused || userPaused ) {
				return;
			}

			items[ current ]?.classList.remove( 'is-current' );
			current = ( current + 1 ) % items.length;
			items[ current ]?.classList.add( 'is-current' );
		}, interval );

		container.addEventListener( 'mouseenter', () => ( hoverPaused = true ) );
		container.addEventListener( 'mouseleave', () => ( hoverPaused = false ) );
		container.addEventListener( 'focusin', () => ( hoverPaused = true ) );
		container.addEventListener( 'focusout', () => ( hoverPaused = false ) );

		pauseButton?.addEventListener( 'click', () => {
			userPaused = ! userPaused;
			pauseButton.setAttribute( 'aria-pressed', String( userPaused ) );
		} );
	} );
}
