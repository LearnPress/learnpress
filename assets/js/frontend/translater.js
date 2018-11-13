;(function ($) {
    if(window.LP === undefined){
        window.LP = {}
    }

    var Translator = function (strings) {
        this.strings = strings;
    };

    $(document).ready(function () {
        window.LP.l10n = new Translator();
    })
})(jQuery);
