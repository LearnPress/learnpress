let iframeCounter = 1;
const $ = window.jQuery || jQuery;

const IframeSubmit = function( form ) {
	const iframeId = 'ajax-iframe-' + iframeCounter;
	let $iframe = $( 'form[name="' + iframeId + '"]' );

	if ( ! $iframe.length ) {
		$iframe = $( '<iframe />' ).appendTo( document.body ).attr( {
			name: iframeId,
			src: '#',
		} );
	}

	$( form ).on( 'submit', function() {
		const $form = $( form ).clone().appendTo( document.body );

		$form.attr( 'target', iframeId );
		$form.find( '#submit' ).remove();

		return false;
	} );

	iframeCounter++;
};

export default IframeSubmit;
