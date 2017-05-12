/**
 * Base controller
 *
 * @plugin LearnPress
 * @author ThimPress
 * @package LearnPress/AdminJS/Controller/Base
 * @version 3.0
 */
;(function ($) {
    /**
     * Base controller
     *
     * @param $scope
     */
    window['learn-press.base.controller'] = function ($scope) {
        angular.extend($scope, {
            test: function () {
                console.log('Test function from Base Controller');
            }
        });
    }
})(jQuery);