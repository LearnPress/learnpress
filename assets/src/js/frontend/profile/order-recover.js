import { lpSetLoadingEl } from '../../utils.js';
import Toastify from 'toastify-js';

/**
 * JS Recover order
 *
 * @since 4.0.0
 * @version 1.0.1
 */
const recoverOrder = () => {
	const toastify = Toastify( {
		gravity: lpData.toast.gravity, // `top` or `bottom`
		position: lpData.toast.position, // `left`, `center` or `right`
		close: lpData.toast.close == 1,
		className: `${ lpData.toast.classPrefix }`,
		stopOnFocus: lpData.toast.stopOnFocus == 1,
		duration: lpData.toast.duration,
	} );

	// Events
	document.addEventListener( 'submit', ( e ) => {
		const target = e.target;
		if ( target.classList.contains( 'lp-order-recover' ) ) {
			e.preventDefault();
			ajaxRecover( target );
		}
	} );

	const ajaxRecover = ( form ) => {
		const status = 'error';
		const btnSubmit = form.querySelector( '.button-recover-order' );
		if ( ! btnSubmit ) {
			return;
		}

		lpSetLoadingEl( btnSubmit, 1 );

		const url = new URL( window.location.href );
		fetch( url, {
			method: 'POST',
			body: new FormData( form ),
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( ( res ) => {
				const { status, data: { redirect }, message } = res;

				if ( status === 'success' ) {
					toastify.options.text = message;
					toastify.options.className += ` ${ status }`;
					toastify.showToast();
					if ( redirect ) {
						window.location.href = redirect;
					}
					btnSubmit.remove();
				} else {
					lpSetLoadingEl( btnSubmit, 0 );
					throw new Error( message );
				}
			} ).finally( () => {

			} ).catch( ( err ) => {
				toastify.options.text = err.message;
				toastify.options.className += ` ${ status }`;
				toastify.showToast();
			} );
	};
};

export default recoverOrder;

