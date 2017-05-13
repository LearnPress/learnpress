;(function ($) {
    window.courseEditor.controller('quiz', ['$scope', '$controller', window['learn-press.quiz.controller']]);

    $(document).ready(function () {
        jQuery('.lp-btn-toggle').click(function(){ jQuery(this).closest('.learn-press-box-data').toggleClass('closed') })

    })
})(jQuery)