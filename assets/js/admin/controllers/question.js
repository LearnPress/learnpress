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
    window['learn-press.question.controller'] = function ($scope, $compile, $element) {
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
                    command = '';
                switch (event.keyCode) {
                    case 13:
                        if(eventType === 'keypress'){
                            command = 'addOption';
                        }

                }
                if(this[command]){
                    this[command]();
                }
                if (eventType === 'keypress' && event.keyCode === 13) {
                    event.preventDefault();
                }
            },
            getOptionPosition: function () {
                return this.questionOptions[1]
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
            addOption: function () {
                var element = angular.element($('#tmpl-question-' + this.questionData.type + '-option').html());
                $compile(element)($scope, function (clonedElement, scope) {
                    $($element).find('.lp-list-options tbody').append(clonedElement);
                    clonedElement.find('.lp-answer-text').focus();
                });
            },
            remove: function () {
                $scope.questionOptions = [];
            }
        });
        $scope.init();
    }
})(jQuery);