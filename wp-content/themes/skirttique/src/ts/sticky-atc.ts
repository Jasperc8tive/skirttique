/**
 * Sticky add-to-bag (Stage 21) — a mobile bar that appears once the
 * purchase form has scrolled out of view. Simple pieces add directly;
 * variable pieces scroll back to the size choice (and once a size is
 * chosen, the bar's label upgrades and it adds directly too).
 * Visibility is mobile-only in CSS; this module only toggles [hidden].
 */

export function initStickyBuy(): void {
	const bar = document.querySelector<HTMLElement>( '[data-st-sticky-buy]' );
	const buy = document.querySelector<HTMLElement>( '[data-st-purchase]' );
	const cta = bar?.querySelector<HTMLButtonElement>( '[data-st-sticky-cta]' );

	if ( ! bar || ! buy || ! cta ) {
		return;
	}

	const add = buy.querySelector<HTMLButtonElement>( '[data-st-add]' );
	const chooseLabel = cta.textContent?.trim() ?? '';

	// Show only once the buy box has been scrolled PAST (above the
	// viewport) — not while it is still ahead of the reader. Computed
	// from the rect on an rAF-throttled scroll listener (parallax.ts
	// pattern) rather than an IntersectionObserver: an instant jump past
	// the element never produces an intersection transition, so an
	// observer would sleep through anchor jumps and fast flicks.
	let ticking = false;
	const update = (): void => {
		ticking = false;
		bar.hidden = buy.getBoundingClientRect().bottom >= 0;
	};
	const request = (): void => {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	};
	window.addEventListener( 'scroll', request, { passive: true } );
	window.addEventListener( 'resize', request, { passive: true } );
	update();

	// A variable piece's bar reads "Choose size" until purchase.ts arms
	// the real Add button, then both act identically.
	const syncLabel = (): void => {
		if ( add && add.hasAttribute( 'data-st-needs-choice' ) ) {
			cta.textContent = add.disabled ? chooseLabel : add.textContent?.trim() ?? chooseLabel;
		}
	};
	if ( add ) {
		new MutationObserver( syncLabel ).observe( add, { attributes: true, attributeFilter: [ 'disabled' ] } );
	}

	cta.addEventListener( 'click', () => {
		if ( add && ! add.disabled ) {
			add.click();
			return;
		}

		const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		buy.scrollIntoView( { behavior: reduced ? 'auto' : 'smooth', block: 'center' } );
		buy.querySelector<HTMLButtonElement>( '.st-buy__option' )?.focus( { preventScroll: true } );
	} );
}
