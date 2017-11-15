;(function ($) {

    "use strict";

    function init() {
        function validateEmail(email) {
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }

        function checkForm($form) {
            var $emails = $form.find('input[type="email"]'),
                valid = true;
            $emails.each(function () {
                var $this = $(this);
                $this.css('border-color', '');

                switch ($this.attr('name')) {
                    case 'settings[paypal][paypal_email]':
                    case 'settings[paypal][paypal_sandbox_email]':
                        if (!$this.closest('tr').prev().find('input[type="checkbox"]').is(':checked')) {
                            return;
                        }
                        break;
                }
                if (!validateEmail(this.value)) {
                    valid = false;
                    $this.css('border-color', '#FF0000');
                }
            });

            return valid;
        }

        $(document).on('click', '.buttons .button', function (e) {
            e.preventDefault();
            var $form = $('#learn-press-setup-form'),
                loadUrl = $(this).attr('href'),
                $main = $('#main');

            if (!checkForm($form)) {
                return;
            }

            $main.addClass('loading');
            $.post({
                url: loadUrl,
                data: $form.serializeJSON(),
                success: function (res) {
                    var $html = $(res);
                    $('#main').replaceWith($html.contents().filter('#main'));

                    LP.setUrl(loadUrl);

                    $('.learn-press-dropdown-pages').dropdownPages();
                    $main.removeClass('loading');
                }
            });
        })
    }

    $(document).ready(init)

})(jQuery);