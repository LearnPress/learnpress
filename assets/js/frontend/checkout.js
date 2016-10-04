if ( typeof window.LP === 'undefined' ) {
    window.LP = {};
}
;
( function ( $ ) {
    "use strict";
    LP.reload = function ( url ) {
        if ( !url ) {
            url = window.location.href;
        }
        window.location.href = url;
    };
    LP.Checkout = {
        $form: null,
        init: function () {
            var $doc = $( document );
            this.$form = $( 'form[name="lp-checkout"]' );
            $doc.on( 'click', 'input[name="payment_method"]', this.selectPaymentMethod );
            $doc.on( 'click', '#learn-press-checkout-login-button', this.login );

            $( 'input[name="payment_method"]:checked' ).trigger( 'click' );
            this.$form.on( 'submit', this.doCheckout );
        },
        selectPaymentMethod: function () {
            if ( $( '.payment-methods input.input-radio' ).length > 1 ) {
                var $paymentForm = $( 'div.payment-method-form.' + $( this ).attr( 'id' ) );
                if ( $( this ).is( ':checked' ) ) { // && !$paymentForm.is(':visible')
                    $( 'div.payment-method-form' ).filter( ':visible' ).slideUp( 250 );
                    $( this ).parents('li:first').find( '.payment-method-form.' + $( this ).attr( 'id' ) ).slideDown( 250 );
                }
            } else {
                $( 'div.payment-method-form' ).show();
            }

            if ( $( this ).data( 'order_button_text' ) ) {
                $( '#learn-press-checkout' ).val( $( '#learn-press-checkout' ).data( 'order_button_text' ) );
            } else {
                $( '#learn-press-checkout' ).val( $( '#learn-press-checkout' ).data( 'value' ) );
            }
        },
        login: function () {
            var $form = $( this.form );
            if ( $form.triggerHandler( 'checkout_login' ) !== false ) {
                $.ajax( {
                    url: LP_Settings.siteurl + '/?lp-ajax=checkout-login',
                    dataType: 'html',
                    data: $form.serialize(),
                    type: 'post',
                    success: function ( response ) {
                        response = LP.parseJSON( response );
                        if ( response.result === 'fail' ) {
                            if ( response.messages ) {
                                LP.Checkout.showErrors( response.messages );
                            } else {
                                LP.Checkout.showErrors( '<div class="learn-press-error">Unknown error!</div>' );
                            }
                        } else {
                            if ( response.redirect ) {
                                window.location.href = response.redirect;
                            }
                        }
                    }
                } );
            }
            return false;
        },
        doCheckout: function () {
            var $form = $( this ),
                    $place_order = $form.find( '#learn-press-checkout' ),
                    processing_text = $place_order.attr( 'data-processing-text' ),
                    text = $place_order.attr( 'value' );
            if ( $form.triggerHandler( 'learn_press_checkout_place_order' ) !== false && $form.triggerHandler( 'learn_press_checkout_place_order_' + $( '#order_review' ).find( 'input[name=payment_method]:checked' ).val() ) !== false ) {
                if ( processing_text ) {
                    $place_order.val( processing_text );
                }
                $place_order.prop( 'disabled', true );
                $.ajax( {
                    url: LP_Settings.siteurl + '/?lp-ajax=checkout',
                    dataType: 'html',
                    data: $form.serialize(),
                    type: 'post',
                    success: function ( response ) {
                        response = LP.parseJSON( response );
                        if ( response.result === 'fail' ) {
                            if ( response.messages ) {
                                LP.Checkout.showErrors( response.messages );
                            } else {
                                LP.Checkout.showErrors( '<div class="learn-press-error">Unknown error!</div>' );
                            }
                        } else if ( response.result === 'success' ) {
                            if ( response.redirect ) {
                                $place_order.val( 'Redirecting' );
                                LP.reload( response.redirect );
                                return;
                            }
                        }
                        $place_order.val( text );
                        $place_order.prop( 'disabled', false );
                    },
                    error: function ( jqXHR, textStatus, errorThrown ) {
                        LP.Checkout.showErrors( '<div class="learn-press-error">' + errorThrown + '</div>' );
                        $place_order.val( text );
                        $place_order.prop( 'disabled', false );

                    }
                } );
            }
            return false;
        },
        showErrors: function ( messages ) {
            $( '.learn-press-error, .learn-press-notice, .learn-press-message' ).remove();
            this.$form.prepend( messages );
            $( 'html, body' ).animate( {
                scrollTop: ( LP.Checkout.$form.offset().top - 100 )
            }, 1000 );
            $( document ).trigger( 'learnpress_checkout_error' );
        }
    };
    $( document ).ready( function () {
        LP.Checkout.init();
    } );
} )( jQuery );