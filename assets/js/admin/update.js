;(function ($) {
    'use strict';

    function init() {
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
        })
    }

    $(document).ready(init);

})(jQuery);
