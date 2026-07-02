/**
 * House Settings — media picker for image fields.
 * Plain JS over wp.media; one frame per field, created lazily.
 */
( function () {
	'use strict';

	document.querySelectorAll( '.st-media-field' ).forEach( function ( field ) {
		var input = field.querySelector( '[data-st-media-input]' );
		var preview = field.querySelector( '[data-st-media-preview]' );
		var pick = field.querySelector( '[data-st-media-pick]' );
		var clear = field.querySelector( '[data-st-media-clear]' );
		var frame = null;

		pick.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					title: pick.textContent,
					multiple: false,
					library: { type: 'image' },
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var thumb =
						( attachment.sizes && ( attachment.sizes.medium || attachment.sizes.full ) ) || attachment;

					input.value = attachment.id;
					preview.src = thumb.url;
					preview.style.display = '';
					clear.style.display = '';
				} );
			}

			frame.open();
		} );

		clear.addEventListener( 'click', function () {
			input.value = '';
			preview.src = '';
			preview.style.display = 'none';
			clear.style.display = 'none';
		} );
	} );
} )();
