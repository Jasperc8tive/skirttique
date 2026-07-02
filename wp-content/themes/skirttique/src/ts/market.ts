/**
 * Market selector — persists the choice and reloads so the server
 * (Skirttique\Core\Services\Market) can react. Stage 9 layers currency
 * conversion on the same cookie.
 */

const COOKIE = 'skirttique_market';
const ONE_YEAR_S = 31536000;

export function initMarket(): void {
	document.querySelectorAll<HTMLElement>( '[data-st-market-option]' ).forEach( ( option ) => {
		option.addEventListener( 'click', () => {
			const code = option.dataset.stMarketOption;
			if ( ! code ) {
				return;
			}

			document.cookie = `${ COOKIE }=${ encodeURIComponent( code ) }; path=/; max-age=${ ONE_YEAR_S }; SameSite=Lax`;
			window.location.reload();
		} );
	} );
}
