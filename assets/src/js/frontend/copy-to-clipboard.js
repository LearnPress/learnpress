import { LPClick } from '../utils';

export default function CopyToClipboard() {
	LPClick( '.social-share-toggle', '.share-toggle-icon', '.content-widget-social-share' );

	var copyTextareaBtn = document.querySelector( '.btn-clipboard' );
	console.log('ádasdasdasd');
	if (copyTextareaBtn) {
		copyTextareaBtn.addEventListener( 'click', function( event ) {
			var copyTextarea = document.querySelector( '.clipboard-value' );
			copyTextarea.focus();
			copyTextarea.select();
			try {
				var successful = document.execCommand( 'copy' );
				var msg = copyTextareaBtn.getAttribute( 'data-copied' );
				copyTextareaBtn.innerHTML = msg + '<span class="tooltip">' + msg + '</span>';
			} catch (err) {

			}
		} );
	}
}
