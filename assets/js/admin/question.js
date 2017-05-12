;(function ($) {
    window.courseEditor.controller('question', ['$scope', '$compile', '$element', window['learn-press.question.controller']]);

    /*courseEditor.directive('contentRendered', function ($timeout) {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                setTimeout(scope.$eval(attrs.contentRendered), 0, element, scope.option);  //Calling a scoped method
            }
        };
    });

    courseEditor.filter("trust", ['$sce', function ($sce) {
        return function (htmlCode) {
            return $sce.trustAsHtml(htmlCode);
        }
    }]);/*.directive('sectionControl', function(){
        return {
            restrict: 'E',
            replace: true,
            transclude: false,
            scope: { items:'=options'},
            template: '<tr ng-repeat="option in items track by $index">xxxxx'+
            '</tr>',
            link: function(scope, element, attrs) {
                scope.getIncludeFile = function(section) {
                    return section.name.toLowerCase().replace('section ','') + ".html";
                }

            }
        }
    });*/

})(jQuery);