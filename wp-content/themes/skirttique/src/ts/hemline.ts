/**
 * The Hemline — signature link underline.
 *
 * Progressive enhancement: links carrying `.st-hemline` receive the
 * two-path SVG underline (straight line at rest, curve on hover/focus —
 * the cross-fade is pure CSS in _hemline.scss). Without JavaScript the
 * link keeps a standard CSS underline, so nothing breaks.
 */

const SVG_NS = 'http://www.w3.org/2000/svg';

function buildUnderline(): SVGSVGElement {
	const svg = document.createElementNS( SVG_NS, 'svg' );
	svg.setAttribute( 'viewBox', '0 0 100 6' );
	svg.setAttribute( 'preserveAspectRatio', 'none' );
	svg.setAttribute( 'aria-hidden', 'true' );
	svg.classList.add( 'st-hemline__svg' );

	const straight = document.createElementNS( SVG_NS, 'path' );
	straight.setAttribute( 'd', 'M0,5 L100,5' );
	straight.classList.add( 'st-hemline__straight' );

	const curve = document.createElementNS( SVG_NS, 'path' );
	curve.setAttribute( 'd', 'M0,5 Q50,0 100,5' );
	curve.classList.add( 'st-hemline__curve' );

	svg.append( straight, curve );
	return svg;
}

export function initHemlines( root: ParentNode = document ): void {
	root
		.querySelectorAll<HTMLAnchorElement>( 'a.st-hemline:not(.is-enhanced)' )
		.forEach( ( link ) => {
			link.classList.add( 'is-enhanced' );
			link.append( buildUnderline() );
		} );
}
