;(function ($) {
    window.courseEditor.service('searchItems', function ($timeout, $rootScope, $http) {
        var that = this;
        this.timeout = null;
        this.results = [];
        this.htmlNavigator = null;
        this.term = '';
        this.search = function (args) {
            $http({
                url: this.getAjaxUrl(),
                method: 'POST',
                data: args.data
            }).then(function (response) {
                that.getHttpJson(response);
                if (args.done) {
                    args.done.apply(this, response);
                }
            });


        }
        this.request = function (args) {

        }
        this.getAjaxUrl = function () {
            return window.location.href.addQueryVar('lp-ajax', 'modal-search-questions');
        }
        this.getHttpJson = function (response) {
            this.results = [];
            if (response && response.data) {
                var response = LP.parseJSON(response.data);
                this.results = response.items || [];
                this.htmlNavigator = response.navigator;
                this.total = response.total;
            }
        }
    });

    window.courseEditor.controller('quiz', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.quiz.controller']]);
    window.courseEditor.controller('modalSearch', ['$scope', '$compile', '$element', '$timeout', '$http', 'searchItems', window['learn-press.modal-search-controller']]);
    window.courseEditor.controller('modalSearchQuestion', ['$scope', '$compile', '$element', '$timeout', '$http', 'searchItems', window['learn-press.modal-search-question']]);
})(jQuery);