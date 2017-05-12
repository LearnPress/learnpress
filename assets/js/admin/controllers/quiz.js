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
        $.extend($scope, {
            questions: quizQuestions,
            addQuestion: function(){
                console.log('add question')
            }
        });
    }
})(jQuery);