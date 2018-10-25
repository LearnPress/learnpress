/**
 * Quiz Component for LearnPress
 *
 * @author ThimPress
 * @version 3.2.0
 */
;(function ($) {
    Vue.component('lp-course-item-lp_quiz', {
        //template: '#tmpl-course-item-content-lp_quiz',
        props: ['item', 'isCurrent', 'currentItem'],
        data: function () {
            return $.extend({}, {
                status: '',
                currentQuestion: 0,
                isLoading: true,
                questionIds: [],
                answers: {},
                isFirst: false,
                isLast: false,
                questions: [],
                hintCount: 0,
                checkCount: 0,
                totalTime: 0,
                timeRemaining: 0,
                clock: {
                    h: '00', m: '00', s: '00'
                },
                xyz: Math.random()
            }, lpQuizQuestions)
        },
        watch: {
            questions: {
                handler: function (v) {
                    return v || [];
                },
                deep: true
            },
            isCurrent: function (a, b) {
                if (a) {
                    if (this.status === '') {
                        this.load();
                    }
                }

                return a;
            },
            status: function (a, b) {
                if (a) {
                    //this.$().closest('.learn-press-content-item').find('.content-item-content').hide();
                }
                return a;
            },
            timeRemaining: function (v) {
                this.clock = this.secondsToTime(v);
                return v;
            }
        },
        computed: {
            isActive: function () {

                return v;
            },
            questionContent: function () {

            }
        },
        mounted: function () {
            if (this.item.id) {
                this.init();
            }
        },
        methods: {
            secondsToTime: function (seconds) {
                var MINUTE_IN_SECONDS = 60,
                    HOUR_IN_SECONDS = 3600,
                    DAY_IN_SECONDS = 24 * 3600;

                if (seconds > DAY_IN_SECONDS) {
                    var days = Math.ceil(seconds / DAY_IN_SECONDS);
                    return {d: days + ( days > 1 ? ' days left' : ' day left' )};
                } else if (seconds) {
                    var hours = Math.floor(seconds / HOUR_IN_SECONDS), minutes;

                    seconds = hours ? seconds % (hours * HOUR_IN_SECONDS) : seconds;
                    minutes = Math.floor(seconds / MINUTE_IN_SECONDS);
                    seconds = minutes ? seconds % (minutes * MINUTE_IN_SECONDS) : seconds;

                    if (hours && hours < 10) {
                        hours = '0' + hours;
                    }

                    if (minutes < 10) {
                        minutes = '0' + minutes;
                    }

                    if (seconds < 10) {
                        seconds = '0' + seconds;
                    }

                    return {
                        h: hours,
                        m: minutes,
                        s: seconds
                    }
                }

                return {
                    h: '00',
                    m: '00',
                    s: '00'
                }
            },
            loadScript: function (url) {
                var script = document.createElement('script');
                script.onload = function () {
                };
                script.src = url;

                document.head.appendChild(script);
            },
            init: function () {
                var $vm = this;
                this.$questions = this.$('.quiz-question');
                this.questionIds = $(this.questions).map(function () {
                    return this.id;
                }).get();

                if (!this.currentQuestion) {
                    this.currentQuestion = this.questionIds[0];
                }

                this.toggleButtons();
                this.$questions.each(function () {
                    var $q = $(this),
                        id = $q.attr('data-id');
                    $vm.fillAnswers($q);
                });

                $(document).ready(LP.debounce(function () {
                    $vm.isLoading = false;

                    $vm.$('.answer-option').on('change', 'input, textarea, select', function () {
                        var $q = $(this).closest('.quiz-question');
                        $vm.fillAnswers($q);
                    });
                    var scripts = [];
                    $vm.$('#learn-press-quiz-' + $vm.item.id).find('script').each(function () {
                        var $script = $(this);

                        if ($script.attr('src')) {
                            $vm.loadScript($script.attr('src'));
                            $script.remove();
                        } else {
                            scripts.push($(this).text());
                        }
                    });

                    if (scripts) {
                        eval.apply(window, [scripts.join("\n\n")]);
                    }

                }, 3000));

                this.timer && clearInterval(this.timer);
                this.timer = setInterval(function ($vm) {
                    $vm.timeRemaining > 0 && $vm.timeRemaining--;
                }, 1000, $vm);
            },

            load: function () {
                var $vm = this;
                $vmCourse._$request(false, 'get-quiz', {itemId: this.item.id, xxx: 1}).then(function (r) {
                    var assignFields = $vm.applyFilters('totalTime timeRemaining checkCount hintCount currentQuestion status questions answers'.split(' '));
                    $vm.$set($vm.item, 'quiz', r);

                    $.each(assignFields, function (a, b) {
                        $vm[b] = r[b];
                    });


                    $vm.init();
                });

            },
            hasQuestions: function () {
                return this.questions && this.questions.length;
            },
            isShowContent: function () {
                return this.item.quiz ? !this.item.quiz.status : true;
            },
            applyFilters: function (args) {
                var filteredArgs = $(document).triggerHandler('LP.quiz-ajax-fields', args);

                return filteredArgs !== undefined ? filteredArgs : args;
            },
            fillAnswers: function ($q) {
                var $vm = this,
                    id = $q.attr('data-id'),
                    answers = [];
                $q.find('.answer-option').find('input[type="checkbox"], input[type="radio"]').filter(':checked').each(function () {
                    answers.push($(this).val());
                });

                $q.find('.answer-option').find('input, select, textarea').each(function () {
                    if ($.inArray($(this).attr('type'), ['checkbox', 'radio']) !== -1) {
                        return;
                    }
                    answers.push($(this).val());
                });

                Vue.set($vm.answers, id, answers);
            },
            toggleButtons: function () {
                var $vm = this;
                if (this.questionIds.length > 1) {
                    this.isFirst = this.questionIds.findIndex(function (e) {
                            return e == $vm.currentQuestion;
                        }) === 0;

                    this.isLast = this.questionIds.findIndex(function (e) {
                            return e == $vm.currentQuestion;
                        }) === this.questionIds.length - 1;
                }
            },
            getQuestionIndex: function (id) {
                return (function (theQuestions, theId) {
                    return theQuestions.findIndex(function (q) {
                        return q == theId;
                    })
                })(this.questionIds, id || this.currentQuestion);
            },
            getQuestion: function (id) {
                return this.questions.find(function (a) {
                    return a.id == id
                })
            },
            $: function (selector) {
                return selector ? $(this.$el).find(selector) : $(this.$el)
            },
            getQuestionContent: function (questionId) {
                var q = this.getQuestionById(questionId);

                return q ? q.content : '';
            },
            mainClass: function () {
                var cls = [this.isLoading ? '' : 'is-loaded', 'learn-press-quiz-content'];

                return cls;
            },
            hasHint: function (questionId) {
                var q = this.getQuestionById(questionId);

                return q && q.hasHint;
            },
            hasExplanation: function (questionId) {
                var q = this.getQuestionById(questionId);

                return q && q.hasExplanation;
            },
            canHintQuestion: function (questionId) {
                var q = this.getQuestionById(questionId);

                return this.hintCount && (q && !q.hinted && q.hasHint);
            },
            canCheckQuestion: function (questionId) {
                questionId = questionId || this.currentQuestion;
                var q = this.getQuestionById(questionId);
                return this.checkCount && (this.answers[questionId] && this.answers[questionId].length) && (q && !q.checked && q.hasExplanation);
            },
            buttonHintLabel: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q && !q.hinted ? 'Hint' : 'Hinted';
            },
            buttonCheckLabel: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q && !q.checked ? 'Check' : 'Checked';
            },
            isCheckedQuestion: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q && q.checked;
            },
            isHintedQuestion: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q && q.hinted;
            },
            getQuestionExplanation: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q ? q.explanation : '';
            },
            getQuestionHint: function (questionId) {
                var q = this.getQuestionById(questionId);
                return q ? q.hint : '';
            },
            getQuestionData: function (data, questionId) {
                questionId = questionId || this.currentQuestion;
                return Vue.http.post('',
                    $.extend({}, data || {}, {
                        'lp-ajax': 'get_question_data',
                        question_id: questionId
                    }),
                    {
                        emulateJSON: true,
                        params: {
                            namespace: 'LPCurriculumRequest'
                        }
                    })
            },
            countQuestions: function () {
                return this.questionIds ? this.questionIds.length : 0;
            },
            checkAnswers: function (questionId, userAnswers) {
                var q = isNaN(questionId) ? questionId : this.getQuestionById(questionId);

                if (q) {
                    q.userAnswers = userAnswers;
                    var $answers = this.$questions.filter('#quiz-question-' + q.id).find('.answer-option').addClass('disabled');
                    $.each(userAnswers, function (i, answer) {

                        var answerClass = [];


                        if (answer.is_true) {
                            answerClass.push('answer-correct');
                        }

                        if (answer.checked) {
                            answerClass.push('answer-selected');
                        }

                        if (answer.checked && answer.is_true) {
                            answerClass.push('answered-correct');
                        } else if (answer.checked && !answer.is_true) {
                            answerClass.push('answered-wrong');
                        } else if (!answer.checked && answer.is_true) {
                            answerClass.push('answered-wrong');
                        }

                        $answers.eq(i).addClass(answerClass.join(' ')).find('input.option-check').prop('checked', answer.checked).prop('disabled', true);
                    })
                }
            },
            getQuestionById: function (questionId) {
                questionId = questionId || this.currentQuestion;
                var at = this.getQuestionIndex(questionId);
                return this.questions ? this.questions[at] : false;
            },
            _prev: function () {
                var at = this.getQuestionIndex();

                if (at > 0) {
                    at--;
                }
                this._moveToQuestion(null, at)
            },
            _next: function () {
                var at = this.getQuestionIndex();

                if (at < this.questionIds.length - 1) {
                    at++;
                }

                this._moveToQuestion(null, at)
            },
            _moveToQuestion: function ($e, at) {
                this.currentQuestion = this.questionIds[at];
                this.toggleButtons();

                var q = this.questions[at];

                if (q && q.permalink) {
                    LP.setUrl(q.permalink)
                }
            },
            _complete: function () {
                jConfirm(LP.l10n.translate('Do you want to finish quiz %s?', this.item.name), '', $.proxy(function (confirm) {
                    $vmCourse._$request(false, 'complete-quiz', {
                        itemId: this.item.id,
                        answers: this.answers
                    });
                }, this));

                setTimeout(function () {
                    $.alerts._reposition();
                    $('#popup_container').addClass('ready')
                }, 30)

                var $a = $('<a href="" class="close"><i class="fa fa-times"></i></a>')
                $('#popup_container').append($a);
                $a.on('click', function () {
                    $.alerts._hide();
                    return false;
                });

                $(document.body).toggleClass('confirm', true);


            },
            _doCheckAnswer: function () {
                var $vm = this,
                    q = this.questions[this.getQuestionIndex()];
                q.checked = true;

                this.getQuestionData({
                    extraAction: 'check-answer',
                    extraData: $.extend({}, q, {answers: this.answers[q.id]})
                }).then(function (r) {
                    var response = LP.parseJSON(r.body);
                    q.explanation = response.explanation;
                    $vm.checkAnswers(q, response.userAnswers)
                    $vm.checkCount--;
                });

            },
            _doHintAnswer: function () {
                var q = this.questions[this.getQuestionIndex()];
                q.hinted = true;
                q.hint = 'Hint: ' + Math.random();
                this.hintCount--;
            },
            /**
             * Start quiz action for starting a quiz
             * @private
             */
            _startQuiz: function () {
                console.log(this.item);

                window.$request('', 'start-quiz', {itemId: this.item.id}).then(function (r) {
                    LP.$vms['notifications'].add(r.notifications)
                })
            },
            _transitionEnter: LP.debounce(function () {
//                    var $el = this.$('.quiz-question:visible');
//                    $el.parent().height($el.height());
//                    console.log('enter', this.currentQuestion)
            }, 10)
        }
    });
})(jQuery);
