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
    window['learn-press.question.controller'] = function ($scope, $compile, $element, $timeout) {
        $element = $($element);
        angular.extend($scope, {
            questionData: {
                title: 'Nodem is pul donor shit met',
                id: 10,
                type: ''
            },
            init: function () {
                this.initData();
                this.bindEvents();
                this.addQuestionData();
                this.addOption();
                this.getListContainer().sortable({
                    handle: '.lp-btn-move',
                    axis: 'y'
                });
                this.tooltip($element);
            },
            initData: function () {
                try {
                    this.questionData = JSON.parse($($element).find('.element-data').html());
                    var types = $element.find('.lp-btn-change-type ul').children().removeClass('active');
                    types.filter('[data-type="' + this.questionData.type + '"]').addClass('active');
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

                // $element.on('')
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
                var optionTemplate = angular.element($('#tmpl-question-' + this.questionData.type + '-option').html()),
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
                    var json = $option.find('input, textarea, select').serializeJSON('learn_press_question[' + $scope.questionData.id + '].answer_options');
                    for (var j in json) {
                        if (j == 'checked') {
                            option['is_true'] = 'yes';
                        } else {
                            option[j] = json[j][0];
                        }
                    }
                    option['answer_order'] = i;
                    options.push(option);
                });
                this.questionData.answer_options = options;
            },
            openLink: function (event, type) {
                switch (type) {
                    case 'edit':
                }
            },
            getListContainer: function () {
                return $element.closest('#learn-press-questions');
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
                        order: order > 0 ? order + 1 : order,
                        quiz_id: this.getScreenQuizId(),
                        context: 'quiz'
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
            getElement: function () {
                return $element;
            },
            toggleContent: function (event) {
                $(event.target).closest('.learn-press-box-data').toggleClass('closed');
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
            moveNextQuestion: function($question, dir){
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
            update: function(event){
                var data = $element.find('input, select, textarea').serializeJSON();
                console.log(this.questionData.title);
            },
            removeQuestion: function () {

            }
        });
        $scope.init();
    }
})(jQuery);
