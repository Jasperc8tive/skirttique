/**
 * Cookie consent (Stage 25) — the quiet banner in the footer pattern.
 * Honest by design: the store sets only essential cookies today, so the
 * banner is information plus a standing choice for the analytics that
 * integrations may add later. The choice lives in a first-party cookie
 * (`skirttique_consent`: all|essential, 180 days); future scripts gate
 * on it server-side or listen for the `st:consent` event client-side.
 * Footer "Cookie preferences" ([data-st-consent-open]) reopens it.
 */

const COOKIE = 'skirttique_consent';
const MAX_AGE = 180 * 24 * 60 * 60;

type Level = 'all' | 'essential';

const read = (): string | null => {
	const match = document.cookie.match( new RegExp( `(?:^|;\\s*)${ COOKIE }=([^;]*)` ) );
	return match?.[ 1 ] !== undefined ? decodeURIComponent( match[ 1 ] ) : null;
};

const save = ( level: Level ): void => {
	document.cookie = `${ COOKIE }=${ level }; max-age=${ MAX_AGE }; path=/; samesite=lax`;
};

export function initConsent(): void {
	const banner = document.querySelector<HTMLElement>( '[data-st-consent]' );
	if ( ! banner ) {
		return;
	}

	if ( read() === null ) {
		banner.hidden = false;
	}

	banner.querySelectorAll<HTMLButtonElement>( '[data-st-consent-choice]' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const level: Level = button.dataset.stConsentChoice === 'all' ? 'all' : 'essential';
			save( level );
			banner.hidden = true;
			document.dispatchEvent( new CustomEvent( 'st:consent', { detail: { level } } ) );
		} );
	} );

	// The footer's "Cookie preferences" — the banner can always be reopened.
	document.querySelectorAll<HTMLElement>( '[data-st-consent-open]' ).forEach( ( opener ) => {
		opener.addEventListener( 'click', () => {
			banner.hidden = false;
			banner.querySelector<HTMLButtonElement>( '[data-st-consent-choice]' )?.focus();
		} );
	} );
}
