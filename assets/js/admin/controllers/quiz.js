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
    window['learn-press.quiz.controller'] = function ($scope, $compile, $element, $timeout, $http) {
        $element = $($element);
        angular.extend($scope, {
            quizData: null,
            init: function () {
                console.log('Quiz init');
                $(document).on('learn-press/add-new-question', function (event, $questionScope) {
                    var $question = $questionScope.getElement(),
                        position = $scope.getListContainer().children().index($question) + 1;
                    $scope.addQuestion(event, {position: position});
                    console.log(position)
                });
                this.initData();
                $element.find('.lp-count-questions').removeClass('hide-if-js');
            },
            addQuestion: function (event, args) {
                var
                    $list = $element.find('#learn-press-questions'),
                    $newQuestion = $($('#tmpl-quiz-question').html()),
                    id = $newQuestion.attr('id');
                args = $.extend({
                    position: -1,
                    type: ''
                }, args || {});
                if (args.position === -1) {
                    $list.append($newQuestion);
                } else {
                    var $el = $list.children().eq(args.position);
                    if ($el.length) {
                        $newQuestion.insertBefore($el);
                    } else {
                        $list.append($newQuestion);
                    }
                }
                var type = !args['type'] ? $(event.target).siblings('.lp-toolbar-btn-dropdown').find('ul li:first').data('type') : args['type']
                $newQuestion.find('.question-id').val(LP.uniqueId('fake-'));
                $newQuestion.find('.question-type').val(type);
                $compile($newQuestion)($scope);
                $newQuestion.find('.lp-question-heading-title').focus();

            },
            initData: function () {
                try {
                    this.quizData = JSON.parse($($element).find('.quiz-element-data').html());
                } catch (ex) {
                    console.log(ex)
                }
            },
            getListContainer: function () {
                return $element.find('#learn-press-questions');
            },
            countQuestion: function (single, plural) {
                var count = $element.find('.learn-press-question').length;
                if (arguments.length === 2) {
                    if (count <= 1) {
                        return single.replace(/%d/, count);
                    } else {
                        return plural.replace(/%d/, count);
                    }
                }
                return count;
            },
            saveAllQuestions: function () {
                var $els = this.getElement('#learn-press-questions').children('.learn-press-question'),
                    postData = {
                        id: this.getScreenPostId('lp_quiz'),
                        questions: {}
                    };
                _.forEach($els, function (el, i) {
                    var ctrl = angular.element(el).scope(),
                        data = ctrl.getFormData();
                    postData.questions[ctrl.getId()] = data;
                });
                $http({
                    method: 'post',
                    url: this.getAjaxUrl('lp-ajax=ajax_update_quiz'),
                    data: postData
                }).then(function (response) {
                });
            },
            cloneQuestion:function (event) {
                var $question = $(event.target).closest('.learn-press-question'),
                    $newQuestion = $question.clone();
                $newQuestion.insertAfter($question);
            },
            toggleContent: function (event) {
                var $btn = $(event.target).closest('.lp-btn-toggle').toggleClass('closed');

                $btn.closest('.learn-press-box-data')
                    .find('.learn-press-question')
                    .toggleClass('closed', $btn.hasClass('closed'));
            }
        });
        $scope.init();
    }
})(jQuery);