<?php
/**
 * Preload all items for Vue template
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.2.0
 */
defined( 'ABSPATH' ) or die;

/**
 * @var LP_Course         $course
 * @var LP_Course_Section $section
 * @var LP_Course_Item    $item
 */
global $lp_course_item;

$course             = LP_Global::course();
$global_course_item = $lp_course_item;

$item_types = learn_press_course_get_support_item_types();
foreach ( $item_types as $item_type => $name ) {
	?>
    <script type="text/html" id="tmpl-course-item-content-<?php echo $item_type; ?>">
        <div>
			<?php do_action( 'learn-press/tmpl-course-item-content', $item_type ); ?>
        </div>
    </script>
<?php } ?>
<div id="learn-press-content-item">

    <div class="content-item-scrollable">

        <div class="content-item-wrap">

            <div :class="mainClass()"
                 data-classes="<?php echo join( ' ', learn_press_content_item_summary_main_classes() ); ?>">
				<?php
				foreach ( $course->get_sections() as $section ) {
					foreach ( $section->get_items() as $item ) {
						$lp_course_item = $item;
						?>
                        <div id="content-item-<?php echo $item->get_id(); ?>"
                             v-show="isShowItem(<?php echo $item->get_id(); ?>)"
                             class="learn-press-content-item content-item-<?php echo $item->get_post_type(); ?>">
                            <lp-course-item :item="currentItem">
                                <div class="content-item-content">
									<?php do_action( 'learn-press/tmpl-course-item-content-description', $item->get_id(), $course->get_id() ) ?>
                                </div>
                                <component :is="getComponent('<?php echo $item->get_post_type(); ?>')"
                                           :item="currentItem"
                                           :is-current="currentItem.id==<?php echo $item->get_id(); ?>"></component>
                            </lp-course-item>
                        </div>
						<?php
					}
				}
				?>
            </div>

        </div>

    </div>

</div>

<?php
// Reset global course item
$lp_course_item = $global_course_item;
?>


<script>
    (function ($) {
        function xxx() {
            return new Vue({
                el: '#learn-press-content-item',
                data: function () {
                    return {
                        loaded: false,
                        courseLoaded: false,
                        currentItem: {},
                        item: {a: 0}
                    }
                },
                computed: {
//                    currentItem: function () {
//                        console.log('currentItem')
//                        return this.$courseStore() ? this.$courseStore().currentItem : {};
//                    },
                    abcx: function () {
                        return this.abc();
                    }
                },
                watch: {
                    courseLoaded: function (newValue) {
                        this.currentItem = this.$courseStore('currentItem');

                        return newValue;
                    },
                    'currentItem.id': function (a, b) {
                        if (a != b) {
                            LP.setUrl(this.currentItem.permalink);
                            this.$('.content-item-scrollable').scrollTop(0);
                        }
                        return a;
                    }
                },
                mounted: function () {
                    var $vm = this;
                    //this.loaded = true;
                    $(document).on('LP.click-curriculum-item', function (e, data) {
                        data.$event.preventDefault();
                        $vm.currentItem = data.item;
                    }).ready(function () {
                        setTimeout(function () {
                            $vm.loaded = true;
                        }, 100);
                        //
                    });
                },
                methods: {
                    getComponent: function (type) {
                        var component = 'lp-course-item-' + type,
                            refComponent = $(document).triggerHandler('LP.get-course-item-component', component, {type: type});

                        if (refComponent) {
                            component = refComponent;
                        }
                        if (!Vue.options.components[component]) {
                            component = 'lp-course-item';
                            console.log('Vue component ' + component + ' does not exist.');
                        }
                        return component;
                    },
                    abc: function () {
                        return Math.random();
                    },
                    isShowItem: function (itemId) {
                        return !this.loaded || this.currentItem.id == itemId;
                    },
                    mainClass: function () {
                        var cls = [this.$().attr('data-classes') || '']

                        if (this.loaded) {
                            cls.push('ready');
                        }

                        cls.push(this.currentItem.type)

                        return cls;
                    },
                    _completeItem: function (e) {
                        //$(document).trigger('LP.complete-item', {$event: e, item: this.currentItem});
                        LP_Event_Bus.$emit('complete-item', {$event: e, item: this.currentItem});
                    },
                    $: function (selector) {
                        return selector ? $(this.$el).find(selector) : $(this.$el);
                    },
                    $courseStore: function (prop, value) {
                        var $store = window.$courseStore;

                        if (!$store) {
                            return undefined;
                        }

                        if (prop) {
                            if (arguments.length == 2) {
                                $store.getters['all'][prop] = value;
                            } else {
                                return $store.getters['all'][prop]
                            }
                        }

                        return $store.getters['all'];
                    }
                }
            });
        }

        var lpQuizQuestions = {};
        var componentDefaults = {
            props: ['item', 'isCurrent'],
            functional: true,
            render: function (createElement, context) {
                return createElement(
                    'div',
                    context.data,
                    context.children)
            }
        }
        Vue.component('lp-course-item', $.extend({}, componentDefaults, {
            getComponent: function (type) {
                var component = 'lp-course-item-' + type,
                    refComponent = $(document).triggerHandler('LP.get-course-item-component', component, {type: type});

                if (refComponent) {
                    component = refComponent;
                }
                if (!Vue.options.components[component]) {
                    component = 'lp-course-item';
                    console.log('Vue component ' + component + ' does not exist.');
                }
                return component;
            },
        }));
        Vue.component('lp-course-item-lp_lesson', $.extend({}, componentDefaults, {
            methods: {
                isShowContent: function () {
                    return true;
                }
            }
        }));

        Vue.component('lp-course-item-lp_quiz', {
            template: '#tmpl-course-item-content-lp_quiz',
            props: ['item', 'isCurrent'],
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
                        this.$().closest('.learn-press-content-item').find('.content-item-content').hide();
                    }
                    return a;
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

                    }, 3000))
                },
                load: function () {
                    var $vm = this;
                    $vmCourse._$request(false, 'get-quiz', {itemId: this.item.id, xxx: 1}).then(function (r) {
                        $vm.$set($vm.item, 'quiz', r);

                        $.each('checkCount hintCount currentQuestion status questions answers'.split(' '), function (a, b) {
                            $vm[b] = r[b];
                        })

                        $vm.init();
                    });

                },
                hasQuestions: function () {
                    return this.questions && this.questions.length;
                },
                isShowContent: function () {
                    return this.item.quiz ? !this.item.quiz.status : true;
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
                }
            }
        });

        var $vm = xxx();

        $(document).on('course-ready', function () {
            $vm.courseLoaded = true;
        });

        window.$vmContentItem = $vm;

    })(jQuery);
</script>
