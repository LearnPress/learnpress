let iframeCounter = 1;
let $ = window.jQuery;

const IframeSubmit = function (form) {
    let iframeId = 'ajax-iframe-' + iframeCounter;
    let $iframe = $('form[name="' + iframeId + '"]');

    if (!$iframe.length) {
        $iframe = $('<iframe />').appendTo(document.body).attr({
            name: iframeId,
            src: '#'
        }).on('load', function () {
            console.log('Loaded')
        });
    }

    $(form).on('submit', function () {

        const $form = $(form).clone().appendTo(document.body);


        $form.attr('target', iframeId);
        $form.find('#submit').remove()
        //$form.submit();
        return false;
    });

    iframeCounter++;
}

export default IframeSubmit;