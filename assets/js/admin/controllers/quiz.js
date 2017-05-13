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
    window['learn-press.quiz.controller'] = function ($scope) {
        angular.extend($scope, {
            questions: quizQuestions,
            init: function(){
                console.log('Quiz init');
            },
            addQuestion: function(){
                console.log('add question')
            }
        });
        $scope.init();
    }
})(jQuery);