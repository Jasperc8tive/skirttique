/**
 * Product slider — scroll-snap rail with arrow paging.
 *
 * The track scrolls natively (touch, wheel, keyboard); arrows page it
 * by one viewport on pointer devices and disable at the ends.
 */

export function initSliders(): void {
	document.querySelectorAll<HTMLElement>( '[data-st-slider]' ).forEach( ( slider ) => {
		const track = slider.querySelector<HTMLElement>( '[data-st-slider-track]' );
		const prev = slider.querySelector<HTMLButtonElement>( '[data-st-slider-prev]' );
		const next = slider.querySelector<HTMLButtonElement>( '[data-st-slider-next]' );

		if ( ! track || ! prev || ! next ) {
			return;
		}

		const update = (): void => {
			const max = track.scrollWidth - track.clientWidth - 1;
			prev.disabled = track.scrollLeft <= 1;
			next.disabled = track.scrollLeft >= max;
		};

		const page = ( direction: 1 | -1 ): void => {
			track.scrollBy( { left: direction * track.clientWidth, behavior: 'smooth' } );
		};

		prev.addEventListener( 'click', () => page( -1 ) );
		next.addEventListener( 'click', () => page( 1 ) );
		track.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update, { passive: true } );
		update();
	} );
}
