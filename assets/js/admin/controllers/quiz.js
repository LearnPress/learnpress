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
            itemsPerRequest: 1,
            totalQuestions: 0,
            updatedQuestions: 0,
            $searchCtrl: null,
            init: function () {
                if ($element.attr('ng-controller') !== 'quiz') {
                    return;
                }
                $(document)
                    .on('learn-press/add-new-question', this.cbAddNewQuestion)
                    .on('learn-press/modal-search/select-items', this.addItemsFromSearch)
                    .on('submit', '#post', this.cbBeforeSubmitForm);

                this.initData();
                $element.find('.lp-count-questions').removeClass('hide-if-js');

                this.initSortableQuestions();
            },
            getUpdatedQuestionsPercent: function () {
                return parseInt(this.updatedQuestions / this.totalQuestions * 100);
            },
            updatedQuestionsPercent: function () {
                var p = this.getUpdatedQuestionsPercent();
                this.getElement('.progress').find('.progress-bar.current').css({
                    width: p + '%'
                }).end().find('.progress-percent').html(p + '%');
            },
            /**
             * Make the list of questions is sortable.
             */
            initSortableQuestions: function () {
                var that = this;
                this.getElement('#lp-list-questions').sortable({
                    items: '> tbody',
                    handle: '.column-sort',
                    axis: 'y', // vertical only
                    update: function () {
                        // do stuff?
                        // $scope.updateQuestionOrders.apply($scope);

                    }
                });
            },

            /**
             * Callback function before submitting form.
             *
             * @param event
             */
            cbBeforeSubmitForm: function (event) {
                if (!$scope.isSaved) {
                    event.preventDefault();
                    $scope.$apply(function () {
                        $scope.totalQuestions = $scope.countQuestions();
                        $scope.updatedQuestions = 0;
                        $scope.doSubmit({
                            done: function () {
                                ///alert('done');
                                //$('#post').submit();
                                $('#publishing-action').find('.spinner').removeClass('is-active').end().find('#publish').removeClass('disabled');
                            }
                        });
                    });
                }
            },

            /**
             * Callback function on add new.
             *
             * @param event
             * @param $questionScope
             */
            cbAddNewQuestion: function (event, $questionScope) {
                var $question = $questionScope.getElement(),
                    position = $scope.getListContainer().children().index($question) + 1;
                $scope.addQuestion(event, {position: position});
            },

            /**
             * Add items from search (questions bank) to quiz.
             * This is callback function for a jQuery event so
             * do not use this to access to $scope.
             *
             * @param event jQuery event
             * @param items array of items to add [{id: '', text: ''}, {...}]
             */
            addItemsFromSearch: function (event, items) {
                var $ctrl = angular.element($('.modal-search-questions').get(0)).scope(),
                    $list = $('#lp-list-questions'),
                    templateHTML = $('#learn-press-empty-question-template').html();

                _.forEach(items, function (data) {
                    // Compile temporary element
                    var $q = $scope.compileQuestion(templateHTML, data).appendTo($list);
                    (function ($quizCtrl, $questionCtrl, $q, data) {
                        $http({
                            url: window.location.href.addQueryVar('lp-ajax', 'get-question-data'),
                            method: 'post',
                            data: {
                                id: data.id
                            }
                        }).then(function (response) {
                            if (response && response.data) {
                                var $l = $quizCtrl.compileQuestion(response.data, data);
                                $q.replaceWith($l);
                            }
                        });
                    })($scope, this, $q, data);
                }, $ctrl);
                $scope.updateQuestionOrders()
                // Refresh search results
                var $search = $ctrl.getSearchCtrl();
                $search.setRequestData({paged: 1});
                $search.request();
            },

            /**
             * Compile question element from a html string with data to bind to.
             *
             * @param html {string}
             * @param data {object}
             * @returns {*}
             */
            compileQuestion: function (html, data) {
                var $question = $compile(html)($scope, function (el, scope) {
                    var $el = $(el)
                        .attr('data-dbid', data.id)
                        .find('.lp-question-heading-title')
                        .val(data.text);
                    return $el;
                });
                return $question;
            },

            /**
             * Split questions into a list of chunks and save them separated.
             * This function is called when the post form being submitted.
             * Stop submitting form until all the chunks are submitted.
             *
             * @param options
             */
            doSubmit: function (options) {
                this.isSubmitting = true;
                var paged = options.paged ? options.paged : 1,
                    $questions = this.getQuestions({
                        paged: paged,
                        limit: this.itemsPerRequest
                    });
                if ($questions.length && paged < 1000) {
                    var postData = {
                        questions: {}
                    }, i = (paged - 1) * this.itemsPerRequest;
                    _.forEach($questions, function (el) {
                        var ctrl = angular.element(el).scope(),
                            id = $(el).data('dbid'),
                            data = ctrl.getFormData({order: ++i});
                        postData.questions[id] = data;
                    });
                    $http({
                        method: 'post',
                        url: $scope.getAjaxUrl('lp-ajax=ajax_bundle_update_quiz_questions&paged=' + paged),
                        data: postData
                    }).then(function (response) {
                        $scope.updatedQuestions += $questions.length;
                        $scope.updatedQuestionsPercent();
                        // Next chunk?
                        options.paged = paged + 1;
                        $scope.doSubmit(options);
                    });
                    return;
                }
                this.updatedQuestionsPercent();
                $timeout(function () {
                    this.isSubmitting = false;
                    this.isSaved = true;
                    if (options.done) {
                        options.done.call(this);
                    }
                }.bind(this), 1000)

            },

            /**
             * Get list of questions in a range (like pagination).
             *
             * @param options
             * @returns {boolean}
             */
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

            /**
             * Update question order
             */
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
            newQuestionIndex: function () {
                return this.getElement('#lp-list-questions').children('tbody').length + 1;
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

            },
            onQuickAddInputKeyEvent: function (event) {

            },
            addNewQuestion: function () {
                var $ctrl = this.getSearchCtrl(),
                    title = $ctrl.searchTerm;

            },
            getSearchCtrl: function () {
                if (!this.$searchCtrl) {
                    this.$searchCtrl = angular.element($('.modal-search-questions').get(0)).scope();
                }
                return this.$searchCtrl;
            },
            getQuestionIndex: function ($ctrl) {
                var index = $ctrl.getElement().index();
                return index;
            },
            countQuestions: function () {
                return this.getElement('.learn-press-question').length;
            },
            htmlCountQuestions: function (singular, plural) {
                var count = this.countQuestions();
                return (count > 1 ? plural : singular).replace('%d', count);
            }
        });
        $scope.init();
    }
})(jQuery);