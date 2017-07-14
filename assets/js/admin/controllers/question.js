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

        angular.extend($scope, {
            $element: $element,
            noncePrefix: 'question-',
            questionData: {
                title: '',
                id: LP.uniqueId(),
                type: ''
            },
            answer_option: {
                value: '',
                text: '',
                is_true: false
            },
            xxxxx: function () {
                $scope.questionData.id = (isNaN(this.questionData.id) ? 0 : parseInt(this.questionData.id)) + 1;
            },
            getXXX: function () {
                return Math.random()
            },
            init: function () {
                $timeout(function () {
                    $scope.initData();
                    $scope.bindEvents();
                    //$scope.addQuestionData();
                    //$scope.addOption();
                    $scope.getListContainer().sortable({
                        handle: '.lp-column-sort',
                        axis: 'y',
                        update: function () {
                            $scope.updateAnswerOrders.apply($scope);
                        }
                    });
                    $scope.tooltip($element);
                });
            },

            /**
             * Update answer ordering
             */
            updateAnswerOrders: function () {
                var postData = {id: $scope.getId(), answers: []};
                this.getListContainer().find('tr.lp-list-option').each(function (i, el) {
                    postData.answers.push({
                        value: $(el).find('.lp-answer-value ').val(),
                        text: $(el).find('.lp-answer-text ').val()
                    });
                    $(el).find('[answer-option-order]').html(i + 1);
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
                    this.questionData = $.extend(this.questionData, this.getFormData());// getElement().find('[name^="lp-question-data"]').serializeJSON("lp-question-data");
                    var id = parseInt(this.getElement().attr('data-dbid'));
                    if (id) {
                        this.questionData.id = id;
                    }
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
                        if (this.isSupport('add-answer-option') && ('keypress' === eventType || 'keydown' === eventType)) {
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
                        if (this.isSupport('add-answer-option') && 'keyup' === eventType) {
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

            /**
             * Callback function to add new option into question when
             * clicking on button.
             *
             * @param event
             * @param args
             * @returns {boolean}
             */
            addOption: function (event, args) {
console.log(this.questionData)
                var $list = $element.find('.lp-list-options tbody'),
                    $option = $list.find('tr:last');

                // If there is an empty option
                if ($option.length && this.isEmptyOption($option)) {
                    $option.find('.lp-answer-text').focus();
                    return false;
                }

                this.answer_option.value = LP.uniqueId();
                var strHTML = $('#tmpl-question-' + $scope.questionData.type + '-option')[0].innerHTML,
                    $newOption;

                strHTML = strHTML.replace(/OPTION_VALUE_PLACEHOLDER/g, LP.uniqueId());
                var optionTemplate = angular.element($(strHTML));

                args = $.extend({
                    position: -1
                }, args || {});

                // Compile element
                $newOption = $compile(optionTemplate)($scope, function (clonedElement, scope) {

                    // Find position to insert new option
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

                    // Build tooltip and focus into text field
                    scope.tooltip(clonedElement);
                    clonedElement.find('.lp-answer-text').focus();

                    return clonedElement;
                });

                // Refresh data
                $timeout(function () {
                    $scope.refreshData();
                });

                return $newOption;
            },

            /**
             * Callback function to remove an option when clicking on button.
             *
             * @param event
             */
            removeOption: function (event) {
                var $option = this.getOptionTarget(event.target),
                    $next = $option.next();

                // If there is no option after the $option is being removed then
                // move to option in previous.
                if ($next.length === 0) {
                    $next = $option.prev();
                }

                // Focus into text field
                if ($next) {
                    $next.find('.lp-answer-text').focus();
                }

                // Remove the $option
                $option.remove();

                // Remove tooltip and update new order
                $(".tipsy").remove();
                $scope.updateAnswerOrders();
            },

            refreshData: function () {
                var $options = $element.find('.lp-list-options tbody').children(),
                    options = [];
                _.forEach($options, function (el, i) {
                    var $option = $(el),
                        option = {};
                    var json = $option.find('input, textarea, select').serializeJSON(this.getFormInputPath() + "answer_options");
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

            /**
             * Get container of list options
             */
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

            /**
             *
             * @param event
             */
            toggleContent: function (event) {
                var hidden = $(event.target).closest('tbody').find('.edit-inline').toggleClass('hide-if-js').hasClass('hide-if-js'),
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

            /**
             * Callback function when user type anything into the text field
             *
             * @param event
             */
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

                // Prevent submitting form if enter key is pressed
                if (('keypress' === eventType || 'keydown' === eventType ) && event.keyCode === 13) {
                    event.preventDefault();
                }
            },

            /**
             * Get question container from a target element (user event)
             *
             * @param target
             * @returns {*}
             */
            getQuestionTarget: function (target) {
                return $(target).closest('.learn-press-question');
            },

            /**
             * Check if an option is empty
             *
             * @param $question
             * @returns {boolean}
             */
            isEmptyQuestion: function ($question) {
                return !$question.find('.lp-question-heading-title').val();
            },

            /**
             * Move (focus) to next question (up or down) from a specific question.
             *
             * @param $question
             * @param dir
             */
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
                var data = $element.find('input, select, textarea').serializeJSON(this.getFormInputPath() + "answer_options");
                console.log(data, this.questionData)
            },
            removeQuestion: function (event) {
                var deletePermanently = $(event.target).data('delete-permanently') === 'yes' ? 1 : 0;
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
                    }
                });
                //
            },
            deletePermanently: function () {
                this.removeQuestion({});
            },
            elementClick: function () {
                $('.tipsy').remove();
            },
            getFormData: function (extra) {
                var formData = this.getElement('input, select, textarea').filter(':not(.abc-xyz)').serializeJSON(this.getFormInputPath()),
                    answerOptions = [];
                if (formData) {
                    var values = _.values(formData);
                    formData = values[0];
                } else {
                    formData = {};
                }
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
                return "learn_press_question";
            },
            getId: function () {
                return this.getElement().attr('dbid');
            },
            isOptionChecked: function (event) {
            },
            changeQuestionType: function (event) {
                var $li = $(event.target).closest('li'),
                    $ul = $li.closest('ul'),
                    type = $li.data('type'),
                    oldType = this.questionData.type;
                this.questionData.type = type;
                $http({
                    url: this.getAjaxUrl('lp-ajax=ajax_change_question_type'),
                    method: 'post',
                    data: {
                        id: this.getId(),
                        from: oldType,
                        to: this.questionData.type
                    }
                }).then(function (response) {
                    var optionTemplate = angular.element($(response.data)),
                        $newOption = $compile(optionTemplate)($scope, function (clonedElement, scope) {
                            scope.tooltip(clonedElement)
                            scope.getElement().replaceWith(clonedElement)
                            return clonedElement;
                        });

                    $timeout(function () {
                        $scope.refreshData();
                    })
                });
                // Force to close dropdown
                $ul.addClass('ng-hide');
                $timeout(function () {
                    $ul.removeClass('ng-hide')
                }, 300);

            },
            isSaved: function () {
                return parseInt(this.questionData.id);
            },
            isValidQuestionType: function () {
                return $.inArray(this.questionData.type, ['none', '']) === -1;
            },
            getPosition: function () {
                var $child = this.getElement().parent().children();
                return $child.index(this.getElement()) + 1;
            },
            isSupport: function (feature, type) {
                var is_support = this.questionData.supports[feature] !== undefined;
                if (type && is_support) {
                    return this.questionData.supports[feature] === type;
                }

                return is_support;
            }
        });
        $scope.init();
    }
})(jQuery);
