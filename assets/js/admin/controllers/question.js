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
    window['learn-press.question.controller'] = function ($scope, $compile, $element) {
        //angular.extend(this, $controller('courseEditor', {$scope: $scope}));

        angular.extend($scope, {
            init: function(){
                console.log('Question init')
            },
            getOptionPosition:function () {
                return this.questionOptions[1]
            },
            updateOption: function (el, option) {
                $(el).html($(option.html).html())
                _.forEach(option.attr, function (value, attr) {
                    if (attr === 'class') {
                        var classes = value.split(/\s+/);
                        for (var i = 0; i < classes.length; i++) {
                            $(el).addClass(classes[i]);
                        }
                    } else {
                        $(el).attr(attr, value);
                    }
                });
            },
            addOption: function () {
                var element = angular.element($('#tmpl-question-multi-choice-option').html());

                $compile(element)($scope, function(clonedElement, scope) {
                    $($element).find('.lp-list-options tbody').append(clonedElement)
                });

                console.log('addOption');
                var i = this.questionOptions.length+1;
                this.questionOptions.push({
                    title: 'Question '+i,
                    value: i,
                    is_true: false
                })
                this.test();
            },
            remove: function () {
                $scope.questionOptions = [];
            }
        });
        $scope.init();
    }
})(jQuery);