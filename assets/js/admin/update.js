;(function ($) {
    'use strict';

    function init() {
        var i18n = window.lpUpdateSettings || {};
        $(document).on('click', '#button-update', function (e) {
            e.preventDefault();
            var $form = $('#learn-press-update-form'),
                loadUrl = $(this).attr('href'),
                $main = $('#main').addClass('loading');
            $('.learn-press-message').remove();
            $.post({
                url: loadUrl,
                data: $form.serializeJSON(),
                success: function (res) {
                    $(res).insertBefore($form);
                    $main.removeClass('loading');
                }
            });
        }).on('click', '.lp-button-upgrade', function (e) {
            e.preventDefault();

            if (!confirm(i18n.i18n_confirm)) {
                return false;
            }

            var $btn = $(this),
                url = $btn.addClass('disabled').attr('href'),
                context = $btn.data('context');
            $.post({
                url: url,
                data: {
                    context: context
                },
                success: function (res) {
                    var $msg = $('<div class="notice notice-success"><p>' + $(res).text() + '</p></div>');
                    if (context == 'message') {
                        $btn.closest('.notice').replaceWith($msg);
                    } else {
                        $msg.insertBefore($btn);
                    }
                }
            });
        })
    }

    $(document).ready(init);

})(jQuery);
