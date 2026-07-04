/**
 * Size chart units (Stage 25) — the cm/in switch on the size guide.
 * The server renders centimetres; every numeric cell carries its cm
 * value in data-st-cm, so switching is pure presentation and the
 * no-JS page is simply the cm table. The chosen unit persists across
 * visits (localStorage), and the table caption announces the change.
 */

const STORAGE_KEY = 'st-size-unit';
const CM_PER_INCH = 2.54;

type Unit = 'cm' | 'in';

const asUnit = ( value: string | null ): Unit | null =>
	value === 'cm' || value === 'in' ? value : null;

/** 68 → "26.8": inches to the nearest tenth, cm left whole. */
const format = ( cm: number, unit: Unit ): string =>
	unit === 'cm' ? String( cm ) : String( Math.round( ( cm / CM_PER_INCH ) * 10 ) / 10 );

export function initSizeChart(): void {
	const charts = document.querySelectorAll<HTMLElement>( '[data-st-size-chart]' );
	if ( ! charts.length ) {
		return;
	}

	const stored = ( (): Unit | null => {
		try {
			return asUnit( window.localStorage.getItem( STORAGE_KEY ) );
		} catch {
			return null;
		}
	} )();

	charts.forEach( ( chart ) => {
		const buttons = chart.querySelectorAll<HTMLButtonElement>( '[data-st-unit]' );
		const cells = chart.querySelectorAll<HTMLElement>( '[data-st-cm]' );
		const caption = chart.querySelector<HTMLElement>( '[data-st-unit-caption]' );

		const apply = ( unit: Unit ): void => {
			cells.forEach( ( cell ) => {
				const cm = Number( cell.dataset.stCm );
				if ( ! Number.isNaN( cm ) ) {
					cell.textContent = format( cm, unit );
				}
			} );

			buttons.forEach( ( button ) => {
				button.setAttribute( 'aria-pressed', button.dataset.stUnit === unit ? 'true' : 'false' );
			} );

			if ( caption ) {
				caption.textContent =
					unit === 'cm' ? 'All measurements in centimetres.' : 'All measurements in inches.';
			}
		};

		buttons.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const unit = asUnit( button.dataset.stUnit ?? null );
				if ( ! unit ) {
					return;
				}
				apply( unit );
				try {
					window.localStorage.setItem( STORAGE_KEY, unit );
				} catch {
					// Private mode — the choice simply doesn't persist.
				}
			} );
		} );

		if ( stored && stored !== 'cm' ) {
			apply( stored );
		}
	} );
}
