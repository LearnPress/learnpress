( function( $, settings ) {
	'use strict';

	if ( window.LP === undefined ) {
		window.LP = {};
	}

	/**
	 * Checkout
	 *
	 * @param options
	 */
	const Checkout = LP.Checkout = function( options ) {
		const $formCheckout = $( '#learn-press-checkout-form' ),

			$formLogin = $( '#learn-press-checkout-login' ),

			$formRegister = $( '#learn-press-checkout-register' ),

			$payments = $( '.payment-methods' ),

			$buttonCheckout = $( '#learn-press-checkout-place-order' ),

			$checkoutEmail = $( 'input[name="guest_email"]' );

		let selectedMethod = '';

		if ( String.prototype.isEmail === undefined ) {
			String.prototype.isEmail = function() {
				return new RegExp( '^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$' ).test( this );
			};
		}

		const needPayment = function() {
			return $payments.length > 0;
		};

		const selectedPayment = function() {
			return $payments.find( 'input[name="payment_method"]:checked' ).val();
		};

		const isLoggedIn = function() {
			return $formCheckout.find( 'input[name="checkout-account-switch-form"]:checked' ).length = 0;
		};

		const getActiveFormData = function() {
			const formName = $formCheckout.find( 'input[name="checkout-account-switch-form"]:checked' ).val();
			const $form = $( '#checkout-account-' + formName );

			return $form.serializeJSON();
		};

		const getPaymentData = function() {
			return $( '#checkout-payment' ).serializeJSON();
		};

		const getPaymentNote = function() {
			return $( '.learn-press-checkout-comment' ).serializeJSON();
		};

		const showErrors = function( errors ) {
			showMessage( errors );
			const firstId = Object.keys( errors )[ 0 ];

			$( 'input[name="' + firstId + '"]:visible' ).trigger( 'focus' );
		};

		const _formSubmit = function( e ) {
			e.preventDefault();

			if ( needPayment() && ! selectedPayment() ) {
				showMessage( 'Please select payment method', true );
				return false;
			}

			let formData = {};

			if ( ! isLoggedIn() ) {
				formData = $.extend( formData, getActiveFormData(), getPaymentNote() );
			}

			formData = $.extend( formData, getPaymentData() );

			removeMessage();

			const btnText = $buttonCheckout.text();

			$.ajax( {
				url: options.ajaxurl + '/?lp-ajax=checkout',
				dataType: 'html',
				data: formData,
				type: 'POST',
				beforeSend() {
					$( '#learn-press-checkout-place-order' ).addClass( 'loading' );
					$buttonCheckout.html( options.i18n_processing );
				},
				success( response ) {
					response = LP.parseJSON( response );

					if ( response.messages ) {
						showErrors( response.messages );
					} else if ( response.message ) {
						showMessage( '<div class="learn-press-message error">' + response.message + '</div>' );
					}

					$( '#learn-press-checkout-place-order' ).removeClass( 'loading' );

					if ( 'success' === response.result ) {
						if ( response.redirect && response.redirect.match( /https?/ ) ) {
							$buttonCheckout.html( options.i18n_redirecting );
							window.location = response.redirect;
						}
					} else {
						$buttonCheckout.html( btnText );
					}
				},
				error( jqXHR, textStatus, errorThrown ) {
					$( '#learn-press-checkout-place-order' ).removeClass( 'loading' );

					showMessage( '<div class="learn-press-message error">' + errorThrown + '</div>' );

					$buttonCheckout.html( btnText );

					LP.unblockContent();
				},
			} );

			return false;
		};

		const _selectPaymentChange = function() {
			const id = $( this ).val(),
				$selected = $payments.children().filter( '.selected' ).removeClass( 'selected' ),
				buttonText = $selected.find( '#payment_method_' + selectedMethod ).data( 'order_button_text' );

			$selected.find( '.payment-method-form' ).slideUp();
			$selected.end().filter( '#learn-press-payment-method-' + id ).addClass( 'selected' ).find( '.payment-method-form' ).hide().slideDown();

			selectedMethod = $selected.find( 'payment_method' ).val();

			if ( buttonText ) {
				$buttonCheckout.html( buttonText );
			}
		};

		/**
		 * Button to switch between mode login/register or place order
		 * in case user is not logged in and guest checkout is enabled.
		 */
		const _guestCheckoutClick = function() {
			const showOrHide = $formCheckout.toggle().is( ':visible' );
			$formLogin.toggle( ! showOrHide );
			$formRegister.toggle( ! showOrHide );

			$( '#learn-press-button-guest-checkout' ).toggle( ! showOrHide );
		};

		/**
		 * Append messages into document.
		 *
		 * @param message
		 * @param wrap
		 */
		const showMessage = function( message, wrap = false ) {
			removeMessage();

			if ( $.isPlainObject( message ) ) {
				Object.keys( message ).reverse().forEach( ( id ) => {
					const m = message[ id ];
					let msg = Array.isArray( m ) ? m[ 0 ] : m;
					const type = Array.isArray( m ) ? m[ 1 ] : '';
					msg = '<div class="learn-press-message ' + ( typeof ( type ) === 'string' ? type : '' ) + '">' + msg + '</div>';
					$formCheckout.prepend( msg );
				} );

				return;
			}

			if ( wrap ) {
				message = '<div class="learn-press-message ' + ( typeof ( wrap ) === 'string' ? wrap : '' ) + '">' + message + '</div>';
			}

			$formCheckout.prepend( message );

			$( 'html, body' ).animate( {
				scrollTop: ( $formCheckout.offset().top - 100 ),
			}, 1000 );

			$( document ).trigger( 'learn-press/checkout-error' );
		};

		/**
		 * Callback function for guest email.
		 *
		 * @private
		 */
		const _checkEmail = function() {
			if ( ! this.value.isEmail() ) {
				return;
			}

			this.timer && clearTimeout( this.timer );

			$checkoutEmail.addClass( 'loading' );

			this.timer = setTimeout( function() {
				$.post( {
					url: window.location.href,
					data: {
						'lp-ajax': 'checkout-user-email-exists',
						email: $checkoutEmail.val(),
					},
					success( response ) {
						const res = LP.parseJSON( response );

						$checkoutEmail.removeClass( 'loading' );

						$( '.lp-guest-checkout-output' ).remove();

						if ( res && res.output ) {
							$checkoutEmail.after( res.output );
						}
					},
				} );
			}, 500 );
		};

		/**
		 * Remove all messages
		 */
		const removeMessage = function() {
			$( '.learn-press-error, .learn-press-notice, .learn-press-message' ).remove();
		};

		/**
		 * Callback function for showing/hiding register form.
		 *
		 * @param e
		 * @param toggle
		 */
		const _toggleRegisterForm = function( e, toggle ) {
			toggle = $formRegister.find( '.learn-press-form-register' ).toggle( toggle ).is( ':visible' );
			$formRegister.find( '.checkout-form-register-toggle[data-toggle="show"]' ).toggle( ! toggle );

			e && ( e.preventDefault(), _toggleLoginForm( null, ! toggle ) );
		};

		/**
		 * Callback function for showing/hiding login form.
		 *
		 * @param e {Event}
		 * @param toggle {boolean}
		 * @private
		 */
		const _toggleLoginForm = function( e, toggle ) {
			toggle = $formLogin.find( '.learn-press-form-login' ).toggle( toggle ).is( ':visible' );

			$formLogin.find( '.checkout-form-login-toggle[data-toggle="show"]' ).toggle( ! toggle );

			e && ( e.preventDefault(), _toggleRegisterForm( null, ! toggle ) );
		};

		/**
		 * Place order action
		 */
		$buttonCheckout.on( 'click', function( e ) {

		} );

		$( '.lp-button-guest-checkout' ).on( 'click', _guestCheckoutClick );
		$( '#learn-press-button-cancel-guest-checkout' ).on( 'click', _guestCheckoutClick );

		$checkoutEmail.on( 'keyup changex', _checkEmail ).trigger( 'changex' );
		$payments.on( 'change select', 'input[name="payment_method"]', _selectPaymentChange );
		$formCheckout.on( 'submit', _formSubmit );
		$payments.children( '.selected' ).find( 'input[name="payment_method"]' ).trigger( 'select' );
		$formLogin.on( 'click', '.checkout-form-login-toggle', _toggleLoginForm );
		$formRegister.on( 'click', '.checkout-form-register-toggle', _toggleRegisterForm );

		$formRegister.find( 'input' ).each( function() {
			if ( ( -1 !== $.inArray( $( this ).attr( 'type' ).toLowerCase(), [ 'text', 'email', 'number' ] ) ) && $( this ).val() ) {
				_toggleRegisterForm();

				return false;
			}
		} );

		$formLogin.find( 'input:not([type="hidden"])' ).each( function() {
			if ( ( -1 !== $.inArray( $( this ).attr( 'type' ).toLowerCase(), [ 'text', 'email', 'number' ] ) ) && $( this ).val() ) {
				_toggleLoginForm();

				return false;
			}
		} );

		// Show form if there is only one form Register or Login
		if ( $formRegister.length && ! $formLogin.length ) {
			_toggleRegisterForm();
		} else if ( ! $formRegister.length && $formLogin.length ) {
			_toggleLoginForm();
		}

		$formCheckout
			.on( 'change', 'input[name="checkout-account-switch-form"]', function() {
				$( this ).next().find( 'input:not([type="hidden"]):visible' ).first().trigger( 'focus' );
			} )
			.on( 'change', '#guest_email', function() {
				$formCheckout.find( '#reg_email' ).val( this.value );
			} )
			.on( 'change', '#reg_email', function() {
				$formCheckout.find( '#guest_email' ).val( this.value );
			} );

		setTimeout( function() {
			$formCheckout.find( 'input:not([type="hidden"]):visible' ).first().trigger( 'focus' );
		}, 300 );
	};

	$( document ).ready( function() {
		if ( typeof lpCheckoutSettings !== 'undefined' ) {
			LP.$checkout = new Checkout( lpCheckoutSettings );
		}
	} );
}( jQuery ) );
