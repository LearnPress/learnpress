/**
 * Question controller
 *
 * @plugin LearnPress
 * @author ThimPress
 * @package LearnPress/AdminJS/Question/Controller
 * @version 3.0
 */
;(function ($) {
    /**
     * Question controller
     *
     * @param $scope
     */
    window['learn-press.modal-search-question'] = function ($scope, $compile, $element, $timeout, $http) {
        $element = $($element);
        angular.extend($scope, {
            init: function(){
                this.config = $element.data();
                this.config =_.omit(this.config, ['$ngControllerController', '$scope'])
            },
            getExcludeItems: function(){
                var $els = $('#lp-list-questions').children('tbody'),
                    items = [];
                _.forEach($els, function (el, i) {
                    items.push($(el).data('dbid'));
                });
                return items;
            }
        });
        $scope.init();
    }
})(jQuery);