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

        function blockContent(block) {
            $('#main').toggleClass('loading', block === undefined ? true : block);
        }

        function getFormData(more) {
            var data = $('#learn-press-setup-form').serializeJSON();

            return $.extend(data, more || {});
        }

        function replaceMainContent(newContent) {
            var $newContent = $(newContent);
            $main.replaceWith($newContent);
            $main = $newContent;
        }

        var $main = $('#main');

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
                data: getFormData(),
                success: function (res) {
                    var $html = $(res);
                    replaceMainContent($html.contents().filter('#main'));

                    LP.setUrl(loadUrl);

                    $('.learn-press-dropdown-pages').dropdownPages();
                    $('.learn-press-tip').QuickTip();
                    $main.removeClass('loading');
                }
            });
        }).on('change', '#currency', function () {
            var m = $(this).children(':selected').html().match(/\((.*)\)/),
                symbol = m ? m[1] : '';
            $('#currency-pos').children().each(function () {

                var $option = $(this),
                    text = $option.html();

                switch ($option.val()) {
                    case 'left':
                        text = text.replace(/\( (.*)69/, '( ' + symbol + '69');
                        break;
                    case 'right':
                        text = text.replace(/9([^0-9]*) \)/, '9' + symbol + ' )');
                        break;
                    case 'left_with_space':
                        text = text.replace(/\( (.*) 6/, '( ' + symbol + ' 6');
                        break;
                    case 'right_with_space':
                        text = text.replace(/9 (.*) \)/, '9 ' + symbol + ' )');
                        break;
                }
                $option.html(text);
            });
        }).on('change', 'input, select', function () {
            var $form = $('#learn-press-setup-form'),
                loadUrl = '',
                $main = $('#main');
            $.post({
                url: loadUrl,
                dataType: 'html',
                data: getFormData({
                        'lp-ajax': 'get-price-format'
                    }
                ),
                success: function (res) {
                    $('#preview-price').html(res);
                }
            });
        }).on('click', '#create-pages', function (e) {
            e.preventDefault();
            blockContent();
            $.post({
                url: $(this).attr('href'),
                dataType: 'html',
                data: getFormData({
                        'lp-ajax': 'setup-create-pages'
                    }
                ),
                success: function (res) {
                    replaceMainContent($(res).contents().filter('#main'));
                    $('.learn-press-dropdown-pages').dropdownPages();
                    blockContent(false);
                }
            });
        }).on('click', '#install-sample-course', function (e) {
            e.preventDefault();

            var $button = $(this);
            blockContent();

            $.post({
                url: $(this).attr('href'),
                dataType: 'html',
                data: {},
                success: function (res) {
                    blockContent(false);
                    $button.replaceWith($(res).find('a:first').addClass('button button-primary'));
                }
            });
        })
    }

    $(document).ready(init)

})(jQuery);