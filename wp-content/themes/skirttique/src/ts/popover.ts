/**
 * Popover controller — the collections panel and the market selector.
 *
 * Click-to-open (not hover-only: touch and keyboard first-class),
 * one popover at a time, closed by Esc, outside click, or re-toggle.
 */

interface PopoverPair {
	toggle: HTMLElement;
	panel: HTMLElement;
}

const pairs: PopoverPair[] = [];

function close( pair: PopoverPair ): void {
	pair.panel.hidden = true;
	pair.toggle.setAttribute( 'aria-expanded', 'false' );
}

function closeAll( except?: PopoverPair ): void {
	pairs.forEach( ( pair ) => {
		if ( pair !== except && ! pair.panel.hidden ) {
			close( pair );
		}
	} );
}

export function initPopovers(): void {
	document.querySelectorAll<HTMLElement>( '[data-st-popover]' ).forEach( ( toggle ) => {
		const id = toggle.dataset.stPopover;
		const panel = id ? document.getElementById( id ) : null;

		if ( ! panel ) {
			return;
		}

		const pair: PopoverPair = { toggle, panel };
		pairs.push( pair );

		toggle.addEventListener( 'click', () => {
			const willOpen = panel.hidden;
			closeAll( pair );
			panel.hidden = ! willOpen;
			toggle.setAttribute( 'aria-expanded', String( willOpen ) );
		} );
	} );

	if ( pairs.length === 0 ) {
		return;
	}

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key !== 'Escape' ) {
			return;
		}

		const open = pairs.find( ( pair ) => ! pair.panel.hidden );
		if ( open ) {
			close( open );
			open.toggle.focus();
		}
	} );

	document.addEventListener( 'click', ( event ) => {
		const target = event.target as Node;
		pairs.forEach( ( pair ) => {
			if ( ! pair.panel.hidden && ! pair.panel.contains( target ) && ! pair.toggle.contains( target ) ) {
				close( pair );
			}
		} );
	} );
}
