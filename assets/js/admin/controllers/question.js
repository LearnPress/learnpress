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
    window['learn-press.question.controller'] = function ($scope, $compile, $element, $timeout, $http) {
        $element = $($element);
        $scope.xxx = function () {

        }
        angular.extend($scope, {
            $element: $element,
            noncePrefix: 'question-',
            questionData: {
                title: 'Nodem is pul donor shit met',
                id: 10,
                type: ''
            },
            init: function () {
                $timeout(function () {
                    $scope.initData();
                    $scope.bindEvents();
                    //$scope.addQuestionData();
                    //$scope.addOption();
                    $scope.getListContainer().sortable({
                        handle: '.lp-btn-move',
                        axis: 'y',
                        update: function () {
                            $scope.updateAnswerOrders.apply($scope);
                        }
                    });
                    $scope.tooltip($element);
                });
            },
            updateAnswerOrders: function () {
                var postData = {id: $scope.getId(), answers: []};
                this.getListContainer().find('tr.lp-list-option').each(function (i, el) {
                    postData.answers.push({
                        value: $(el).find('.lp-answer-value ').val(),
                        text: $(el).find('.lp-answer-text ').val()
                    });
                });
                $http({
                    method: 'post',
                    url: $scope.getAjaxUrl('lp-ajax=ajax_update_question_answer_orders'),
                    data: postData
                }).then(function (response) {
                });
            },
            getTitle: function () {
                return $('<textarea />').html(this.questionData.title).text();
            },
            initData: function () {
                try {
                    this.questionData = this.getElement().find('[name^="lp-question-data"]').serializeJSON("['lp-question-data']");
                } catch (ex) {
                    console.log(ex)
                }
            },
            bindEvents: function () {
                $element.on('focus', 'input', function () {
                    $element.addClass('focused');
                }).on('blur', 'input', function () {
                    $element.removeClass('focused');
                });

                this.$doc.on('change update-me', '.abc-xyz', function (e) {
                    $scope.getElement('.def-123').each(function () {
                        $(this).toggleClass('abc-xyz', !this.checked).prev().toggleClass('abc-xyz', this.checked);
                    })
                });
            },

            onOptionKeyEvent: function (event) {
                var eventType = event.type,
                    val = event.target.value,
                    $option = this.getOptionTarget(event.target);
                switch (event.keyCode) {
                    case 13:
                        if ('keypress' === eventType || 'keydown' === eventType) {
                            if (!this.isEmptyOption($option)) {
                                var position = this.getOptionPosition(event.target) + 1;
                                var $e = this.addOption(event, {
                                    position: position
                                });
                            }
                        }
                        break;
                    case 38:
                    case 40:
                        if ('keydown' === eventType) {
                            this.moveNextOption(this.getOptionTarget(event.target), event.keyCode === 38 ? 'prev' : 'next')
                        }
                        break;
                    case 8:
                        if ('keyup' === eventType) {
                            if (val.length === 0) {
                                if ($option.hasClass('lp-option-empty')) {
                                    this.removeOption(event);
                                } else {
                                    $option.addClass('lp-option-empty');
                                }
                            }
                        }

                }

                if (('keypress' === eventType || 'keydown' === eventType ) && event.keyCode === 13) {
                    event.preventDefault();
                }
            },
            getOptionPosition: function (el) {
                var $child = $element.find('.lp-list-options tbody').children(),
                    $el = $(el).closest('tr');
                return $child.index($el);
            },
            getOptionTarget: function (target) {
                return $(target).closest('tr')
            },
            isEmptyOption: function ($option) {
                return !$option.find('.lp-answer-text').val();
            },
            moveNextOption: function ($option, dir) {
                var $next = false;
                if ('next' === dir) {
                    $next = $option.next();
                    if ($next.length === 0 && $option.find('.lp-answer-text').val().length) {
                        $next = this.addOption();
                    }
                } else {
                    $next = $option.prev();
                }
                if ($next) {
                    $next.find('.lp-answer-text').focus();
                }
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
            addOption: function (event, args) {
                var $list = $element.find('.lp-list-options tbody'),
                    $option = $list.find('tr:last');
                if ($option.length && this.isEmptyOption($option)) {
                    $option.find('.lp-answer-text').focus();
                    return;
                }
                var optionTemplate = angular.element($('#tmpl-question-' + $scope.questionData.type + '-option').html()),
                    $newOption = false;
                args = $.extend({
                    position: -1
                }, args || {});
                $newOption = $compile(optionTemplate)($scope, function (clonedElement, scope) {
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
                    scope.tooltip(clonedElement)
                    clonedElement.find('.lp-answer-text').focus();
                    console.log(clonedElement)

                    return clonedElement;
                });
                $timeout(function () {
                    $scope.refreshData();
                })
                return $newOption;
            },
            removeOption: function (event) {
                var $option = this.getOptionTarget(event.target),
                    $prev = $option.next();
                if ($prev.length === 0) {
                    $prev = $option.prev();
                }
                $option.remove();
                if ($prev) {
                    $prev.find('.lp-answer-text').focus();
                }
                $(".tipsy").remove();
            },
            refreshData: function () {
                var $options = $element.find('.lp-list-options tbody').children(),
                    options = [];
                _.forEach($options, function (el, i) {
                    var $option = $(el),
                        option = {};
                    var json = $option.find('input, textarea, select').serializeJSON(this.getFormInputPath() + "['answer_options']");
                    for (var j in json) {
                        if (j == 'checked') {
                            option['is_true'] = 'yes';
                        } else {
                            option[j] = json[j][0];
                        }
                    }
                    option['answer_order'] = i;
                    options.push(option);
                }, this);
                this.questionData.answer_options = options;
            },
            openLink: function (event, type) {
                switch (type) {
                    case 'edit':
                }
            },
            getListContainer: function () {
                return $element.find('.lp-list-options tbody');
            },
            addQuestionData: function () {
                var id = $element.find('.question-id').val();
                if (parseInt(id) > 0) {
                    return;
                }
                var order = this.getListContainer().children('.learn-press-box-data').index($element);
                $.ajax({
                    url: '',
                    data: {
                        'lp-ajax': 'ajax_add_question',
                        type: $element.find('.question-type').val(),
                        title: $element.find('.lp-question-heading-title').val(),
                        order: order > 0 ? order + 1 : order,
                        quiz_id: this.getScreenQuizId(),
                        context: 'quiz',
                        nonce: this.getNonce(),
                        extra_data: this.getFormData()
                    },
                    type: 'post',
                    success: function (response) {
                        $scope.$apply(function () {
                            $.extend($scope.questionData, LP.parseJSON(response));
                            $element.attr('id', 'learn-press-question-' + $scope.questionData.id)
                        });
                    }
                })
            },
            toggleContent: function (event) {
                var hidden = $(event.target).closest('.learn-press-box-data').toggleClass('closed').hasClass('closed'),
                    postData = {hidden: {}};
                postData.hidden[this.getId()] = hidden ? 'yes' : 'no';
                $http({
                    method: 'post',
                    url: this.getAjaxUrl('lp-ajax=ajax_closed_question_box'),
                    data: postData
                }).then(/* Todo: anything here after ajax is completed */function (response) {
                });
            },
            getScreenQuizId: function () {
                return 'lp_quiz' === $('#post_type').val() ? parseInt($('#post_ID').val()) : 0;
            },
            onQuestionKeyEvent: function (event) {
                var eventType = event.type,
                    val = event.target.value,
                    $option = this.getQuestionTarget(event.target);
                switch (event.keyCode) {
                    case 13:
                        if ('keypress' === eventType || 'keydown' === eventType) {
                            if (!this.isEmptyQuestion($option)) {
                                $(document).triggerHandler('learn-press/add-new-question', $scope, $option);
                            }
                        }
                        break;
                    case 38:
                    case 40:
                        if ('keydown' === eventType) {
                            this.moveNextQuestion(this.getQuestionTarget(event.target), event.keyCode === 38 ? 'prev' : 'next')
                        }
                        break;
                    case 8:
                        if ('keyup' === eventType) {
                            if (val.length === 0) {
                                if ($option.hasClass('lp-question-empty')) {
                                    this.removeQuestion(event);
                                } else {
                                    $option.addClass('lp-question-empty');
                                }
                            }
                        }

                }
                if (eventType === 'blur') {
                    this.questionData.title = event.target.value;
                    this.update(event);
                }
                if (('keypress' === eventType || 'keydown' === eventType ) && event.keyCode === 13) {
                    event.preventDefault();
                }
            },
            getQuestionTarget: function (target) {
                return $(target).closest('.learn-press-question');
            },
            isEmptyQuestion: function ($question) {
                return !$question.find('.lp-question-heading-title').val();
            },
            moveNextQuestion: function ($question, dir) {
                var $next = false;
                if ('next' === dir) {
                    $next = $question.next();
                    if ($next.length === 0 && $question.find('.lp-question-heading-title').val().length) {
                        $next = this.addQuestion();
                    }
                } else {
                    $next = $question.prev();
                }
                if ($next) {
                    $next.find('.lp-question-heading-title').focus();
                }
            },
            update: function (event) {
                var data = $element.find('input, select, textarea').serializeJSON(this.getFormInputPath() + "['answer_options']");
                console.log(data, this.questionData)
            },
            removeQuestion: function (event) {
                var deletePermanently = $(event.target).data('delete-permanently') === 'yes';
                $element.addClass('being-deleted');
                $.ajax({
                    url: '',
                    type: 'post',
                    data: {
                        'lp-ajax': 'ajax_delete_quiz_question',
                        quiz_id: this.getScreenQuizId(),
                        id: this.questionData.id,
                        nonce: this.getNonce(),
                        extra_data: $.extend(this.getFormData() || {}, {delete_permanently: deletePermanently})
                    },
                    success: function (response) {
                        response = LP.parseJSON(response);
                        if (response.result === 'success') {
                            $element.remove();
                        } else {
                            $element.removeClass('being-deleted');
                        }
                        $scope.$apply();
                        console.log('xxx')
                    }
                });
                //
            },
            deletePermanently: function () {
                this.removeQuestion({});
            },
            elementClick: function () {
                $('.tipsy').remove();
                console.log()
            },
            getFormData: function (extra) {
                var formData = this.getElement('input, select, textarea').filter(':not(.abc-xyz)').serializeJSON(this.getFormInputPath()) || {},
                    answerOptions = [];
                formData.answer_options && _.forEach(formData.answer_options.text, function (text, i) {
                    answerOptions.push({
                        text: text,
                        value: formData.answer_options.value[i],
                        is_true: formData.answer_options.checked[i] ? 'yes' : 'no'
                    });
                }, this);
                formData.answer_options = answerOptions;
                return this.applyFilters('learn-press/question-form-data', $.extend(formData, extra || {}), extra);
            },
            getFormInputPath: function () {
                return "['learn_press_question']['" + this.questionData.id + "']";
            },
            isOptionChecked: function (event) {
                console.log(event)
            },
            changeQuestionType: function (event) {
                var type = $(event.target).closest('li').data('type');
                this.questionData.type = type;
            }
        });
        $scope.init();
    }
})(jQuery);
