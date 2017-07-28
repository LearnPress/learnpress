;(function ($) {
    if (window.LP === undefined) {
        window.LP = {};
    }

    /**
     * Checkout
     *
     * @type {LP.Checkout}
     */
    var Checkout = LP.Checkout = function () {
        var
            /**
             * Checkout form
             *
             * @type {form}
             */
            $formCheckout = $('#learn-press-checkout'),

            /**
             * Register form
             *
             * @type {form}
             */
            $formLogin = $('#learn-press-checkout-register'),

            /**
             * Login form
             *
             * @type {form}
             */
            $formRegister = $('#learn-press-checkout-login'),

            /**
             * Payment method wrap
             *
             * @type {*}
             */
            $payments = $('.payment-methods'),

            $buttonCheckout = $('#learn-press-checkout-place-order');

        /**
         * Button to switch between mode login/register or place order
         * in case user is not logged in and guest checkout is enabled.
         */
        $('.lp-button-guest-checkout').on('click', function () {
            var showOrHide = $formCheckout.toggle().is(':visible');
            $formLogin.toggle(!showOrHide);
            $formRegister.toggle(!showOrHide);
            $('#learn-press-button-guest-checkout').toggle(!showOrHide);
        });

        /**
         * Place order action
         */
        $buttonCheckout.on('click', function(e){
            e.preventDefault();
            var data = $payments.children('.selected').find('.payment-method-form').serializeJSON();
            console.log(data);
        });

        /**
         * Show payment form on select
         */
        $payments.on('change select', 'input[name="payment_method"]', function () {
            var id = $(this).val(),
                $selected = $payments.children().filter('.selected').removeClass('selected');

            $selected.find('.payment-method-form').slideUp();
            $selected.end().filter('#learn-press-payment-method-' + id).addClass('selected').find('.payment-method-form').hide().slideDown();
        });

        $payments.children('.selected').find('input[name="payment_method"]').trigger('select');
    }

    $(document).ready(function () {
        new Checkout();

    })

})(jQuery);