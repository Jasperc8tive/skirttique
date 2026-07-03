/**
 * Subtle parallax — elements carrying `data-st-parallax="factor"` drift
 * against scroll. rAF-throttled transform-only work; never runs under
 * reduced motion.
 */

interface ParallaxItem {
	el: HTMLElement;
	factor: number;
}

export function initParallax(): void {
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const items: ParallaxItem[] = Array.from(
		document.querySelectorAll<HTMLElement>( '[data-st-parallax]' )
	).map( ( el ) => ( {
		el,
		factor: Math.min( 0.3, Math.max( 0.05, Number( el.dataset.stParallax ) || 0.12 ) ),
	} ) );

	if ( reduced || items.length === 0 ) {
		return;
	}

	let ticking = false;

	const apply = (): void => {
		ticking = false;
		const viewport = window.innerHeight;

		for ( const { el, factor } of items ) {
			const rect = el.getBoundingClientRect();
			if ( rect.bottom < 0 || rect.top > viewport ) {
				continue;
			}
			// Drift proportional to the element's progress through the viewport.
			const progress = ( rect.top + rect.height / 2 - viewport / 2 ) / viewport;
			el.style.transform = `translateY(${ ( progress * factor * 100 ).toFixed( 2 ) }px)`;
		}
	};

	const queue = (): void => {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( apply );
		}
	};

	window.addEventListener( 'scroll', queue, { passive: true } );
	window.addEventListener( 'resize', queue, { passive: true } );
	apply();
}
