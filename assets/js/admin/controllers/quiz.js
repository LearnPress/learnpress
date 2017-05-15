/**
 * Question controller
 *
 * @plugin LearnPress
 * @author ThimPress
 * @package LearnPress/AdminJS/Quiz/Controller
 * @version 3.0
 */
;(function ($) {
    /**
     * Question controller
     *
     * @param $scope
     */
    window['learn-press.quiz.controller'] = function ($scope, $compile, $element, $timeout) {
        angular.extend($scope, {
            quizData: null,
            init: function(){
                console.log('Quiz init');
                this.initData();
            },
            addQuestion: function(event){
                var questionTemplate = angular.element($('#tmpl-quiz-question').html()),
                    $newQuestion = false;
                args = $.extend({
                    position: -1
                }, args || {});
                $newQuestion = $compile(questionTemplate)($scope, function (clonedElement, scope) {
                    if (args.position === -1) {
                        $list.append(clonedElement);
                    } else {
                        var $el = $list.children().eq(args.position);
                        if ($el.length) {
                            clonedElement.insertBefore($el);
                        } else {
                            $list.append(clonedElement);
                        }
                    }
                    clonedElement.find('.lp-answer-text').focus();
                    return clonedElement;
                });
            },
            initData: function () {
                try {
                    this.quizData = JSON.parse($($element).find('.quiz-element-data').html());
                } catch (ex) {
                    console.log(ex)
                }
            },
        });
        $scope.init();
    }
})(jQuery);