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
        //angular.extend(this, $controller('courseEditor', {$scope: $scope}));
        $element = $($element);
        angular.extend($scope, {
            questionData: {
                title: 'Nodem is pul donor shit met',
                id: 10,
                options: [
                    {name: 'Option 1', value: 'option-1', is_true: false},
                    {name: 'Option 2', value: 'option-2', is_true: true},
                    {name: 'Option 3', value: 'option-3', is_true: false}
                ]
            },
            init: function () {
                this.initData();
                this.bindEvents();
            },
            initData: function () {
                try {
                    this.questionData = JSON.parse($($element).find('.element-data').html());
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
                                console.log(2222, $e.html())
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
                    for(var j in json){
                        if(j == 'checked') {
                            option['is_true'] = 'yes';
                        }else{
                            option[j] = json[j][0];
                        }
                    }
                    option['answer_order'] = i;
                    options.push(option);
                });
                this.questionData.answer_options = options;
            },
            openLink: function(event, type){
                switch (type){
                    case 'edit':
                }
            }
        });
        $scope.init();
    }
})(jQuery);