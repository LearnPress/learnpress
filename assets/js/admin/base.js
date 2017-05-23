;(function ($) {
    window.courseEditor = angular.module('courseEditor', []);
    courseEditor.controller('courseEditor', ['$scope', '$compile', '$element', '$timeout', window['learn-press.base.controller']]);
    courseEditor.filter('htmlentities_decode', function($sce){
        return function(input){
            if(input)
                return $sce.trustAsHtml(input)
        };
    });

    $(document).on('click', '.learn-press-tooltip', function(){
        $('.tipsy').remove();
    })
})(jQuery);