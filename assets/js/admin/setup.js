;(function ($) {

    "use strict";

    function init() {
        $(document).on('click', '.buttons .button', function (e) {
            e.preventDefault();
            var $form = $('#learn-press-setup-form'),
                loadUrl = $(this).attr('href'),
                $main = $('#main').addClass('loading');
            console.log($form.serializeJSON());
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