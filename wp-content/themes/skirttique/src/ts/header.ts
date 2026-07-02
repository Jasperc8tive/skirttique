/**
 * Header chrome — hairline appears once the page scrolls.
 */

export function initHeader(): void {
	const header = document.querySelector<HTMLElement>( '[data-st-header]' );
	if ( ! header ) {
		return;
	}

	const update = (): void => {
		header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
	};

	update();
	window.addEventListener( 'scroll', update, { passive: true } );
}
