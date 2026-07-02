/**
 * Drawer controller — native <dialog> as the primitive.
 *
 * `showModal()` provides the focus trap, Esc handling, and top-layer
 * stacking; this module adds opener wiring, backdrop-click close, and
 * focus restoration to the opener.
 */

export function initDrawers(): void {
	const openers = document.querySelectorAll<HTMLElement>( '[data-st-drawer-open]' );

	openers.forEach( ( opener ) => {
		const id = opener.dataset.stDrawerOpen;
		const dialog = id ? document.getElementById( id ) : null;

		if ( ! ( dialog instanceof HTMLDialogElement ) ) {
			return;
		}

		opener.addEventListener( 'click', () => {
			dialog.showModal();
		} );

		dialog.addEventListener( 'close', () => {
			opener.focus();
		} );
	} );

	document.querySelectorAll<HTMLDialogElement>( 'dialog.st-drawer' ).forEach( ( dialog ) => {
		// Close buttons inside the drawer.
		dialog.querySelectorAll<HTMLElement>( '[data-st-drawer-close]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => dialog.close() );
		} );

		// Backdrop click: the dialog itself is the event target only when
		// the click lands outside its content box.
		dialog.addEventListener( 'click', ( event ) => {
			if ( event.target === dialog ) {
				const rect = dialog.getBoundingClientRect();
				const inside =
					event.clientX >= rect.left &&
					event.clientX <= rect.right &&
					event.clientY >= rect.top &&
					event.clientY <= rect.bottom;

				if ( ! inside ) {
					dialog.close();
				}
			}
		} );
	} );
}
