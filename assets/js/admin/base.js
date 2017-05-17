;(function ($) {
    window.courseEditor = angular.module('courseEditor', []);
    courseEditor.controller('courseEditor', ['$scope', '$controller', window['learn-press.base.controller']]);
    courseEditor.filter('htmlentities_decode', function($sce){
        return function(input){
            if(input)
                return $sce.trustAsHtml(input)
        };
    });
})(jQuery);