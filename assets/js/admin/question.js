;(function ($) {
    var questionApp = angular.module('questionApp', []);
    questionApp.controller('questionCtrl', function ($scope) {
        $.extend($scope, {
            questionOptions: questionOptions,
            updateOption:function(el, option){
                $(el).html($(option.html).html())
                _.forEach(option.attr, function(value, attr){
                    if(attr === 'class'){
                        var classes = value.split(/\s+/);
                        for(var i=0;i<classes.length; i++){
                            $(el).addClass(classes[i]);
                        }
                    }else{
                        $(el).attr(attr, value);
                    }
                });
            },
            remove: function(){
                $scope.questionOptions = [];
            },
            xxxx: function(){
                console.log('xxxx');
            }
        });
    })
    questionApp.directive('contentRendered', function ($timeout) {
        return {
            restrict: 'A',
            //scope: {option: '=option'},
            link    : function (scope, element, attrs) {
                setTimeout(scope.$eval(attrs.contentRendered), 0, element, scope.option);  //Calling a scoped method
            }
        };
    });

    questionApp.filter("trust", ['$sce', function ($sce) {
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