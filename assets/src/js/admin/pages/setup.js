import isEmail from '../../utils/email-validator';

;(function ($) {
    "use strict";
    var $main,
        $setupForm;

    const checkForm = function checkForm($form) {
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
            if (!isEmail(this.value)) {
                valid = false;
                $this.css('border-color', '#FF0000');
            }
        });

        return valid;
    }

    const blockContent = function blockContent(block) {
        $main.toggleClass('loading', block === undefined ? true : block);
    }

    const getFormData = function getFormData(more) {
        var data = $setupForm.serializeJSON();

        return $.extend(data, more || {});
    }

    const replaceMainContent = function replaceMainContent(newContent) {
        var $newContent = $(newContent);
        $main.replaceWith($newContent);
        $main = $newContent;
    }

    const navPages = function navPages(e) {
        e.preventDefault();
        var loadUrl = $(this).attr('href');

        if (!checkForm($setupForm)) {
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

                $setupForm = $('#learn-press-setup-form');
                $('.learn-press-dropdown-pages').LP('DropdownPages');
                $('.learn-press-tip').LP('QuickTip');
                $('.learn-press-select2').select2();
                $main.removeClass('loading');
            }
        });
    }

    const updateCurrency = function updateCurrency() {
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
    }

    const updatePrice = function updatePrice() {
        $.post({
            url: '',
            dataType: 'html',
            data: getFormData({
                    'lp-ajax': 'get-price-format'
                }
            ),
            success: function (res) {
                $('#preview-price').html(res);
            }
        })
    }

    const createPages = function createPages(e) {
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
                $('.learn-press-dropdown-pages').LP('DropdownPages');
                blockContent(false);
            }
        });
    }

    const installSampleCourse = function installSampleCourse(e) {
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
    }

    function onReady() {

        $main = $('#main');
        $setupForm = $('#learn-press-setup-form');
        $('.learn-press-select2').select2();

        $(document)
            .on('click', '.buttons .button', navPages)
            .on('change', '#currency', updateCurrency)
            .on('change', 'input, select', updatePrice)
            .on('click', '#create-pages', createPages)
            .on('click', '#install-sample-course', installSampleCourse)
    }

    $(document).ready(onReady)

})(jQuery);