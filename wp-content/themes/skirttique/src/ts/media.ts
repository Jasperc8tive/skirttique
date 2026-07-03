/**
 * Media behaviours: click-to-zoom images and the ambient-video
 * reduced-motion guard.
 */

/**
 * Zoom — figures carrying `data-st-zoom` toggle a close-up on click or
 * Enter/Space, zooming toward the pointer; Esc releases.
 */
export function initZoom(): void {
	const figures = document.querySelectorAll<HTMLElement>( '[data-st-zoom]' );
	if ( figures.length === 0 ) {
		return;
	}

	figures.forEach( ( figure ) => {
		figure.setAttribute( 'tabindex', '0' );
		figure.setAttribute( 'role', 'button' );
		figure.setAttribute( 'aria-pressed', 'false' );

		const toggle = ( event?: MouseEvent ): void => {
			const zoomed = figure.classList.toggle( 'is-zoomed' );
			figure.setAttribute( 'aria-pressed', String( zoomed ) );

			if ( zoomed && event ) {
				const rect = figure.getBoundingClientRect();
				const x = ( ( event.clientX - rect.left ) / rect.width ) * 100;
				const y = ( ( event.clientY - rect.top ) / rect.height ) * 100;
				figure.style.setProperty( '--st-zoom-origin', `${ x.toFixed( 1 ) }% ${ y.toFixed( 1 ) }%` );
			}
		};

		figure.addEventListener( 'click', ( event ) => toggle( event ) );
		figure.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Enter' || event.key === ' ' ) {
				event.preventDefault();
				toggle();
			}
			if ( event.key === 'Escape' && figure.classList.contains( 'is-zoomed' ) ) {
				toggle();
			}
		} );
	} );
}

/**
 * Ambient videos autoplay muted — unless the visitor prefers reduced
 * motion, in which case they hold their poster and gain controls.
 */
export function initAmbientVideo(): void {
	if ( ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	document.querySelectorAll<HTMLVideoElement>( 'video[data-st-ambient]' ).forEach( ( video ) => {
		video.removeAttribute( 'autoplay' );
		video.pause();
		video.controls = true;
	} );
}
