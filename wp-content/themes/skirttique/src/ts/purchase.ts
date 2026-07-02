/**
 * Purchase form — the PDP summary and the quick view share this.
 *
 * Variable products render one button group per attribute with the
 * slimmed variation map embedded as JSON (`data-st-variations`, see
 * inc/woocommerce.php). Selection resolves a variation, updates the
 * price, and arms the Add button; adding POSTs to the plugin's
 * variation-aware cart endpoint.
 */

import { postCart } from './cart';

interface SlimVariation {
	variation_id: number;
	attributes: Record<string, string>;
	is_in_stock: boolean;
	price_html: string;
}

function wireScope( scope: HTMLElement ): void {
	const productId = scope.dataset.stProductId ?? '';
	const addButton = scope.querySelector<HTMLButtonElement>( '[data-st-add]' );
	const priceEl = scope.querySelector<HTMLElement>( '[data-st-price]' );
	const note = scope.querySelector<HTMLElement>( '[data-st-buy-note]' );
	const groups = Array.from( scope.querySelectorAll<HTMLElement>( '[data-st-attr]' ) );

	const variations: SlimVariation[] = scope.dataset.stVariations
		? ( JSON.parse( scope.dataset.stVariations ) as SlimVariation[] )
		: [];

	const chosen = new Map<string, string>();
	let variationId = 0;
	const basePrice = priceEl?.innerHTML ?? '';

	const setNote = ( text: string ): void => {
		if ( note ) {
			note.textContent = text;
			note.hidden = text === '';
		}
	};

	const resolve = (): void => {
		if ( groups.length === 0 ) {
			return; // Simple product — always armed.
		}

		if ( chosen.size < groups.length ) {
			return; // Selection incomplete; button stays disabled.
		}

		const match = variations.find( ( variation ) =>
			Object.entries( variation.attributes ).every(
				( [ key, value ] ) => value === '' || chosen.get( key ) === value
			)
		);

		variationId = match && match.is_in_stock ? match.variation_id : 0;

		if ( priceEl ) {
			priceEl.innerHTML = match && match.price_html !== '' ? match.price_html : basePrice;
		}

		if ( addButton ) {
			addButton.disabled = variationId === 0;
		}

		setNote( match && ! match.is_in_stock ? __OUT_OF_STOCK : '' );
	};

	groups.forEach( ( group ) => {
		const attrKey = group.dataset.stAttr ?? '';
		const options = Array.from( group.querySelectorAll<HTMLButtonElement>( '[data-st-option]' ) );

		options.forEach( ( option ) => {
			option.addEventListener( 'click', () => {
				options.forEach( ( other ) =>
					other.setAttribute( 'aria-pressed', String( other === option ) )
				);
				chosen.set( attrKey, option.dataset.stOption ?? '' );
				resolve();
			} );
		} );
	} );

	addButton?.addEventListener( 'click', () => {
		if ( addButton.disabled || addButton.getAttribute( 'aria-busy' ) === 'true' ) {
			return;
		}

		const body = new URLSearchParams( { product_id: productId, quantity: '1' } );
		if ( variationId > 0 ) {
			body.set( 'variation_id', String( variationId ) );
			chosen.forEach( ( value, key ) => body.append( `attributes[${ key }]`, value ) );
		}

		addButton.setAttribute( 'aria-busy', 'true' );
		postCart( 'skirttique_add_to_cart', body )
			.catch( () => undefined )
			.finally( () => addButton.removeAttribute( 'aria-busy' ) );
	} );
}

// Localized once at build level would be ideal; until i18n tooling lands
// (Stage 11), the string ships here and matches the PHP-side voice.
const __OUT_OF_STOCK = 'Out of stock in this size — another may be available.';

/**
 * Wire every purchase form under `root` exactly once (the quick view
 * injects new ones after load).
 */
export function initPurchaseScopes( root: ParentNode = document ): void {
	root.querySelectorAll<HTMLElement>( '[data-st-purchase]:not([data-st-wired])' ).forEach( ( scope ) => {
		scope.dataset.stWired = 'true';
		wireScope( scope );
	} );
}
