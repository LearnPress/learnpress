;(function ($) {

    var $doc = $(document),
        isRunning = false;

    $doc.on('click', '#learn-press-install-sample-data', function (e) {
        e.preventDefault();

        var $button = $(this);

        if (isRunning) {
            return;
        }

        if (!confirm(lpGlobalSettings.i18n.confirm_install_sample_data)) {
            return;
        }

        $button.addClass('disabled').html($button.data('installing-text'));
        $('.lp-install-sample-data-response').remove();
        isRunning = true;
        $.ajax({
            url: $button.attr('href'),
            data: $('.lp-install-sample-data-options').serializeJSON(),
            success: function (response) {
                $button.removeClass('disabled').html($button.data('text'));
                isRunning = false;
                $(response).insertBefore($button.parent());
            },
            error: function () {
                $button.removeClass('disabled').html($button.data('text'));
                isRunning = false;
            }
        });
    }).on('click', '#learn-press-install-sample-data-options', function (e) {
        e.preventDefault();
        $('.lp-install-sample-data-options').toggleClass('hide-if-js');
    });

})(jQuery);