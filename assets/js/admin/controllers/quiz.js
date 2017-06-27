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
            data: null,
            noncePrefix: 'quiz-',
            isSaved: false,
            isSubmitting: false,
            itemsPerRequest: 2,
            init: function () {
                if ($element.attr('ng-controller') !== 'quiz') {
                    return;
                }
                $(document).on('learn-press/add-new-question', function (event, $questionScope) {
                    var $question = $questionScope.getElement(),
                        position = $scope.getListContainer().children().index($question) + 1;
                    $scope.addQuestion(event, {position: position});
                });
                this.initData();
                $element.find('.lp-count-questions').removeClass('hide-if-js');
                $scope.getListContainer().sortable({
                    handle: '.lp-btn-move',
                    axis: 'y',
                    update: function () {
                        $scope.updateQuestionOrders.apply($scope);
                    }
                });
                $(document).on('click', '#publish', function (e) {

                })
                $('#post').on('submit', function (e) {
                    if (!$scope.isSaved) {
                        e.preventDefault();
                        $scope.$apply(function () {
                            $scope.doSubmit({
                                done: function () {
                                    alert('done')
                                }
                            });
                        });
                    }
                })
            },
            doSubmit: function (options) {
                this.isSubmitting = true;
                var paged = options.paged ? options.paged : 1,
                    loop = 0,
                    $questions = this.getQuestions({
                        paged: paged,
                        limit: this.itemsPerRequest
                    });
                if ($questions.length && paged < 1000) {
                    var postData = {
                        questions: {}
                    }, i=0;
                    _.forEach($questions, function (el) {
                        var ctrl = angular.element(el).scope(),
                            data = ctrl.getFormData({order: i + 1});
                        postData.questions[data.id] = data;
                    });
                    console.log(postData)
                    $timeout(function () {
                        options.paged = paged + 1;
                        $scope.doSubmit(options);
                    }, 3000)

                }
            },
            getQuestions: function (options) {
                options = $.extend({
                    paged: 1,
                    limit: -1
                }, options);
                var $questions = false,
                    start = 0,
                    end = 1000,
                    selector = '';
                if (options.limit > 0 && options.paged) {
                    start = (options.paged - 1) * options.limit;
                    end = start + options.limit;
                }
                selector = '[ng-controller="question"]:lt(' + end + ')';
                if (start > 0) {
                    selector += ':gt(' + (start - 1) + ')';
                }
                $questions = this.getElement(selector);
                return $questions;

            },
            updateQuestionOrders: function () {
                var postData = {id: $scope.getScreenPostId(), questions: []};
                $element.find('.learn-press-question').each(function (i, el) {
                    var ctrl = angular.element(el).scope();
                    postData.questions.push($(el).data('dbid'));
                });
                $http({
                    method: 'post',
                    url: $scope.getAjaxUrl('lp-ajax=ajax_update_quiz_question_orders'),
                    data: postData
                }).then(function (response) {
                });
            },
            addQuestion: function (event, args) {
                var
                    $list = $element.find('#learn-press-questions'),
                    $newQuestion = $($('#tmpl-quiz-question').html()),
                    id = $newQuestion.attr('id');
                args = $.extend({
                    position: -1,
                    id: 0,
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
                var type = !args['type'] ? $(event.target).siblings('.lp-toolbar-btn-dropdown').find('ul li:first').data('type') : args['type'];
                $newQuestion.find('.question-id').val(LP.uniqueId('fake-'));
                $newQuestion.find('.question-type').val(type);
                $compile($newQuestion)($scope);
                $newQuestion.toggleClass('closed', this.data.closed)
                $newQuestion.find('.lp-question-heading-title').focus();
            },
            addExistsQuestions: function (ids) {
                var $list = $element.find('#learn-press-questions'),
                    $tmpl = $('#tmpl-quiz-question').html();

                $http({
                    url: this.getAjaxUrl('lp-ajax=ajax_add_quiz_questions'),
                    data: {
                        id: this.getScreenPostId(),
                        questions: ids
                    },
                    method: 'post'
                }).then(function (r) {
                    var response = $scope.getHttpJson(r);
                    if (response.questions) {
                        _.forEach(response.questions, function (html, id) {
                            var $newQuestion = $(html);
                            $newQuestion.find('.question-id').val(id);
                            $compile($newQuestion)($scope);
                            $newQuestion.toggleClass('closed', this.data.closed)
                            $newQuestion.find('.lp-question-heading-title').focus();
                            $list.append($newQuestion);
                            $scope.$doc.triggerHandler('learn-press/added-quiz-question', id);
                        }, $scope)
                    }
                });
            },
            initData: function () {
                try {
                    this.data = JSON.parse($($element).find('.quiz-element-data').html());
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
                        data = ctrl.getFormData({order: i + 1});
                    postData.questions[data.id] = data;
                });
                $http({
                    method: 'post',
                    url: this.getAjaxUrl('lp-ajax=ajax_update_quiz'),
                    data: postData
                }).then(function (response) {
                });
            },
            removeAllQuestions: function (event) {
                var $questions = this.getElement('.learn-press-question');
                $questions.addClass('being-deleted');
                $.ajax({
                    url: '',
                    type: 'post',
                    data: {
                        'lp-ajax': 'ajax_clear_quiz_question',
                        quiz_id: this.getScreenPostId(),
                        nonce: this.getNonce(),
                        ids: $questions.map(function () {
                            return $(this).data('dbid')
                        }).get()
                        //extra_data: $.extend(this.getFormData() || {}, {delete_permanently: deletePermanently})
                    },
                    success: function (response) {
                        response = LP.parseJSON(response);
                        if (response.result === 'success') {
                            _.forEach($questions, function (question) {
                                var $question = $(question),
                                    id = $question.data('dbid');
                                if (response.ids && response.ids[id]) {
                                    $question.remove();
                                } else {
                                    $question.removeClass('being-deleted');
                                }
                            });
                        } else {
                            $questions.removeClass('being-deleted');
                        }
                        $scope.$apply();
                    }
                });
            },
            cloneQuestion: function (event) {
                var $question = $(event.target).closest('.learn-press-question'),
                    $newQuestion = $question.clone();
                $newQuestion.insertAfter($question);
            },
            toggleContent: function (event) {
                var $btn = $(event.target).closest('.lp-btn-toggle').toggleClass('closed'),
                    closed = $btn.hasClass('closed'),
                    postData = {hidden: {}};

                $btn.closest('.learn-press-box-data')
                    .find('.learn-press-question')
                    .toggleClass('closed', closed)
                    .map(function () {
                        postData.hidden[$(this).data('dbid')] = closed ? 'yes' : 'no'
                    });
                postData.hidden[this.getScreenPostId()] = closed ? 'yes' : 'no';
                $http({
                    method: 'post',
                    url: this.getAjaxUrl('lp-ajax=ajax_closed_question_box'),
                    data: postData
                }).then(/* Todo: anything here after ajax is completed */function (response) {
                });
            },
            showModalSearchItems: function () {

            }
        });
        $scope.init();
    }
})(jQuery);