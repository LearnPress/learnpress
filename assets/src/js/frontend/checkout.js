/**
 * File JS handling checkout page.
 */

import { lpAddQueryArgs, lpFetchAPI, lpAjaxParseJsonOld } from '../utils.js';

// Events
document.addEventListener( 'submit', ( e ) => {
	window.lpCheckout.submit( e );
} );
document.addEventListener( 'change', ( e ) => {
	window.lpCheckout.paymentSelect( e );
} );
document.addEventListener( 'keyup', ( e ) => {
	window.lpCheckout.checkEmailGuest( e );
} );

window.lpCheckout = {
	idFormCheckout: 'learn-press-checkout-form',
	idBtnPlaceOrder: 'learn-press-checkout-place-order',
	classPaymentMethod: 'lp-payment-method',
	classPaymentMethodForm: 'payment-method-form',
	timeOutCheckEmail: null,
	fetchAPI: ( url, params, callBack ) => {
		const option = { headers: {} };
		if ( 0 !== parseInt( lpData.user_id ) ) {
			option.headers[ 'X-WP-Nonce' ] = lpData.nonce;
		}

		const searchParams = new URLSearchParams();
		Object.keys( params ).forEach( ( key ) => {
			searchParams.append( key, params[ key ] );
		} );

		option.method = 'POST';
		option.body = searchParams;

		fetch( url, option )
			.then( ( res ) => res.text() )
			.then( ( data ) => {
				data = lpAjaxParseJsonOld( data );
				callBack.success( data );
			} )
			.finally( () => {
				callBack.completed();
			} )
			.catch( ( err ) => callBack.error( err ) );
	},
	submit: ( e ) => {
		const formCheckout = e.target;
		if ( formCheckout.id !== window.lpCheckout.idFormCheckout ) {
			return;
		}

		if ( formCheckout.classList.contains( 'processing' ) ) {
			return;
		}

		e.preventDefault();

		formCheckout.classList.add( 'processing' );
		const btnSubmit = formCheckout.querySelector( 'button[type="submit"]' );
		btnSubmit.disabled = true;

		window.lpCheckout.removeMessage();
		const elBtnPlaceOrder = document.getElementById( window.lpCheckout.idBtnPlaceOrder );

		const urlHandle = new URL( lpCheckoutSettings.ajaxurl );
		urlHandle.searchParams.set( 'lp-ajax', 'checkout' );

		// get values from FormData
		const formData = new FormData( formCheckout );
		const dataSend = Object.fromEntries( Array.from( formData.keys(), ( key ) => {
			const val = formData.getAll( key );

			return [ key, val.length > 1 ? val : val.pop() ];
		} ) );

		elBtnPlaceOrder.classList.add( 'loading' );

		const callBack = {
			success: ( response ) => {
				response = lpAjaxParseJsonOld( response );
				const { messages, result } = response;
				if ( 'success' !== result ) {
					window.lpCheckout.showErrors( formCheckout, 'error', messages );
				} else {
					window.location.href = response.redirect;
				}
			},
			error: ( error ) => {
				window.lpCheckout.showErrors( formCheckout, 'error', error );
			},
			completed: () => {
				elBtnPlaceOrder.classList.remove( 'loading' );
				formCheckout.classList.remove( 'processing' );
				btnSubmit.disabled = false;
			},
		};

		window.lpCheckout.fetchAPI( urlHandle, dataSend, callBack );
	},
	paymentSelect: ( e ) => {
		const target = e.target;
		const elPaymentMethod = target.closest( `.${ window.lpCheckout.classPaymentMethod }` );
		if ( ! elPaymentMethod ) {
			return;
		}

		const elUlPaymentMethods = elPaymentMethod.closest( '.payment-methods' );
		if ( ! elUlPaymentMethods ) {
			return;
		}

		const elPaymentMethods = elUlPaymentMethods.querySelectorAll( `.${ window.lpCheckout.classPaymentMethod }` );
		elPaymentMethods.forEach( ( el ) => {
			const elPaymentMethodForm = el.querySelector( `.${ window.lpCheckout.classPaymentMethodForm }` );
			if ( ! elPaymentMethodForm ) {
				return;
			}

			if ( elPaymentMethod !== el ) {
				elPaymentMethodForm.style.display = 'none';
			} else {
				elPaymentMethodForm.style.display = 'block';
			}
		} );
	},
	checkEmailGuest: ( e ) => {
		const target = e.target;
		if ( target.id !== 'guest_email' ) {
			return;
		}

		if ( ! window.lpCheckout.isEmail( target.value ) ) {
			return;
		}

		target.classList.add( 'loading' );

		if ( window.lpCheckout.timeOutCheckEmail !== null ) {
			clearTimeout( window.lpCheckout.timeOutCheckEmail );
		}

		window.lpCheckout.timeOutCheckEmail = setTimeout( () => {
			const callBack = {
				success: ( response ) => {
					const { message, data, status } = response;
					if ( 'success' === status ) {
						const content = data.content || '';
						const elGuestOutput = document.querySelector( '.lp-guest-checkout-output' );
						if ( elGuestOutput ) {
							elGuestOutput.remove();
						}

						target.insertAdjacentHTML( 'afterend', content );
					} else {
						window.lpCheckout.showErrors( target.closest( 'form' ), status, message );
					}
				},
				error: ( error ) => {
					window.lpCheckout.showErrors( target.closest( 'form' ), 'error', error );
				},
				completed: () => {
					target.classList.remove( 'loading' );
				},
			};

			window.lpCheckout.fetchAPI(
				window.location.href,
				{
					'lp-ajax': 'checkout-user-email-exists',
					email: target.value,
				},
				callBack,
			);
		}, 500 );
	},
	removeMessage: () => {
		const lpMessage = document.querySelector( '.learn-press-message' );

		if ( ! lpMessage ) {
			return;
		}
		lpMessage.remove();
	},
	showErrors: ( form, status, message ) => {
		const mesHtml = `<div class="learn-press-message ${ status }">${ message }</div>`;
		form.insertAdjacentHTML( 'afterbegin', mesHtml );
		form.scrollIntoView();
	},
	isEmail: ( email ) => {
		return new RegExp( '^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$' ).test( email );
	},
};
