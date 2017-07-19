;(function ($) {
    window
        .courseEditor
        .controller('question', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.question.controller']])
        .directive('answerOptionOrder', function () {
            var link = function (scope, element, attrs) {
                $(element).html($(element).closest('.lp-list-option').index() + 1)
            }
            return {
                restrict: 'EA',
                link: link
            };
        });
})(jQuery);