
import { loadScript } from "@paypal/paypal-js";
import Toastify from 'toastify-js';
import { lpAddQueryArgs, lpFetchAPI, lpAjaxParseJsonOld } from '../../utils';

export default async function payPalCheckoutButton() {
    const checkButtonContainer = () => {
        if (!document.querySelector('#lp-paypal-button-container')) {
            return;
        }
    }
    checkButtonContainer();
    let paypal, lpCheckoutForm;
    try {
        paypal = await loadScript({ clientId: lpCheckoutSettings.paypalClientId });
    } catch (error) {
        console.error("failed to load the PayPal JS SDK script", error);
    }

    if (paypal) {
        try {
            await paypal.Buttons({
                async createOrder() {
                    try {
                        let urlHandle = new URL(lpCheckoutSettings.ajaxurl);
                        urlHandle.searchParams.set('lp-ajax', 'checkout');
                        lpCheckoutForm = document.querySelector(`#${lpCheckout.idFormCheckout}`);
                        const formData = new FormData(lpCheckoutForm);
                        const response = await fetch(urlHandle, {
                            method: 'POST',
                            headers: {
                                'X-WP-Nonce': lpData.nonce
                            },
                            body: formData,
                        });

                        const checkoutResponse = await response.text();
                        const parseResponse = lpAjaxParseJsonOld(checkoutResponse);
                        const orderData = parseResponse.paypal;
                        if (!orderData.id) {
                            const errorDetail = orderData.details[0];
                            const errorMessage = errorDetail
                                ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
                                : "Unexpected error occurred, please try again.";
                            throw new Error(errorMessage);
                        }
                        console.log(orderData);
                        return orderData.id;
                    } catch (error) {
                        console.error(error);
                        throw error;
                    }
                },
                async onApprove(data, actions) {
                    // console.log( data );
                    const order = await actions.order.capture();
                    // console.log(order);
                    handlePaymentCompleted( order );
                },
            }).render("#lp-paypal-button-container");
        } catch (error) {
            showMessage('error', error);
        }
    }
    const showMessage = (status, message) => {
        Toastify({
            text: message,
            gravity: lpData.toast.gravity, // `top` or `bottom`
            position: lpData.toast.position, // `left`, `center` or `right`
            className: `${lpData.toast.classPrefix} ${status}`,
            close: lpData.toast.close == 1,
            stopOnFocus: lpData.toast.stopOnFocus == 1,
            duration: lpData.toast.duration,
        }).showToast();
    };
    const handlePaymentCompleted = async (  data ) => {
        try {
            showMessage( 'success', lpCheckoutSettings.i18n_processing );
            const url = `${lpData.lp_rest_url}lp/v1/paypal-checkout/order`;
            const lpOrderID = data.purchase_units[0].custom_id;
            const response = await fetch( url, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': lpData.nonce,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    orderID: data.id,
                    lpOrderID: lpOrderID
                }),
            });
            const checkoutResponse = await response.json();
            if ( checkoutResponse.status == 'error' ) {
                throw new Error( checkoutResponse.message );
            }
            window.location.href = checkoutResponse.data.redirect_url;
        } catch (error) {
            console.error('Error processing successful payment:', error);
            showMessage( 'error', error );
        }
    };
}