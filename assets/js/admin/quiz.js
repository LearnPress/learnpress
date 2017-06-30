;(function ($) {
    window.courseEditor.controller('quiz', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.quiz.controller']]);
    window.courseEditor.controller('modalSearch', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.modal-search-controller']]);
    window.courseEditor.controller('modalSearchQuestion', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.modal-search-question']]);
})(jQuery);