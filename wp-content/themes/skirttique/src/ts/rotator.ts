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
 */

const DEFAULT_INTERVAL_MS = 6000;

export function initRotators(): void {
	document.querySelectorAll<HTMLElement>( '[data-st-rotate]' ).forEach( ( container ) => {
		const items = Array.from(
			container.querySelectorAll<HTMLElement>( '[data-st-rotate-item]' )
		);
		const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( items.length < 2 || reduced ) {
			return;
		}

		const interval = Number.parseInt( container.dataset.stRotate ?? '', 10 ) || DEFAULT_INTERVAL_MS;

		let current = Math.max(
			items.findIndex( ( item ) => item.classList.contains( 'is-current' ) ),
			0
		);
		let paused = false;

		window.setInterval( () => {
			if ( paused ) {
				return;
			}

			items[ current ]?.classList.remove( 'is-current' );
			current = ( current + 1 ) % items.length;
			items[ current ]?.classList.add( 'is-current' );
		}, interval );

		container.addEventListener( 'mouseenter', () => ( paused = true ) );
		container.addEventListener( 'mouseleave', () => ( paused = false ) );
		container.addEventListener( 'focusin', () => ( paused = true ) );
		container.addEventListener( 'focusout', () => ( paused = false ) );
	} );
}
