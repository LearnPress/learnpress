window.rwmb = window.rwmb || {};

jQuery( function ( $ ) {
	'use strict';

	var views = rwmb.views = rwmb.views || {},
		ImageField = views.ImageField,
		ImageUploadField,
		UploadButton = views.UploadButton;

	ImageUploadField = views.ImageUploadField = ImageField.extend( {
		createAddButton: function () {
			this.addButton = new UploadButton( {controller: this.controller} );
		}
	} );

	/**
	 * Initialize fields
	 * @return void
	 */
	function init() {
		new ImageUploadField( {input: this, el: $( this ).siblings( 'div.rwmb-media-view' )} );
	}

	$( ':input.rwmb-image_upload, :input.rwmb-plupload_image' ).each( init );
	$( '.rwmb-input' )
		.on( 'clone', ':input.rwmb-image_upload, :input.rwmb-plupload_image', init )
} );
