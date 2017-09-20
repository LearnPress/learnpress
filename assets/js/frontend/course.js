;
/**
 * LearnPress frontend course app.
 *
 * @version 3.x.x
 * @author ThimPress
 * @package LearnPress/JS/Course
 */
(function ($, LP, _, Vue, Vuex) {

    'use strict';
    window.Course_01 = {
        postUrl: window.location.href,
        items: {
            9: {
                timeRemaining: 3600,
                totalTime: 3600
            },
            100: {
                title: '',

            }
        }
    }

    LP.Module = {};

    (function (data) {
        var getters = {
            totalTime: function (state) {
                console.log(state)
                console.log('XXXX')
                return state.totalTime;
            },
            timeRemaining: function (state) {
                return state.timeRemaining;
            }
        };

        var mutations = {
            'TICK': function (state) {
                state.timeRemaining--;
            }
        };

        var actions = {
            tick: function (context) {
                context.commit('TICK');
            },
            startCountdown: function () {
                alert();
            }
        };

        LP.Module.Quiz = {
            state: data,
            getters: getters,
            mutations: mutations,
            actions: actions,
        };
    })(Course_01.items[9]);

    (function (data) {
        var state = data;

        var getters = {
            item: function (state) {
                return state.next;
            }
        };

        var mutations = {
            'next': function (state, status) {
                state.heartbeat = !!status;
            },
            checkQuestion: function (event) {
                event.preventDefault();
            },
        };

        var actions = {
            next: function (context) {
                console.log('next')
            }
        };

        LP.Course = new Vuex.Store({
            state: state,
            getters: getters,
            mutations: mutations,
            actions: actions,
            modules: {
                quiz: LP.Module.Quiz,
                //     i18n: LP_Curriculum_i18n_Store,
                //     ss: LP_Curriculum_Sections_Store
            }
        });
    })(Course_01);


    (function (Vue, $store) {
        Vue.component('lp_quiz_component', {
            template: '#content-item-quiz',
            created: function () {
                this.startCountdown()
            },
            computed: {
                totalTime: function () {
                    return $store.getters['quiz/totalTime'];
                }
            },
            methods: {
                completeItem: function () {

                },
                startCountdown: function () {
                    console.log($store.getters['totalTime'])
                },
                checkQuestion: function (event) {
                    event.preventDefault();
                    var $form = $(event.target).is('form') ? $(event.target) : $(event.target.form);

                    console.log($form.serializeJSON())
                },
                showHint: function (event) {
                    event.preventDefault();
                    var $form = $(event.target).is('form') ? $(event.target) : $(event.target.form);

                    console.log($form.serializeJSON())
                }
            }
        });

    })(Vue, LP.Course);

    (function (Vue, $store) {
        Vue.component('lp_lesson_component', {
            template: '#content-item-lesson',
            created: function () {
                this.startCountdown()
            },
            computed: {
                totalTime: function () {
                    return $store.getters['quiz/totalTime'];
                }
            },
            methods: {
                startCountdown: function () {
                }

            }
        });

    })(Vue, LP.Course);

    $(document).ready(function () {
        LP.$Course = new Vue({
            el: '#lp-single-course',
            created: function () {

            },
            computed: {
                totalTime: function () {
                    return LP.Course.getters['quiz/totalTime'];
                }
            },
            methods: {

                showItem: function (event) {
                    console.log(event.target);
                    return false;
                },
                clickX: function () {
                    console.log('clickX')
                },
                nextQuestion: function (event) {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                prevQuestion: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                redoQuiz: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                startQuiz: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                completeItem: function (event) {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                    return false;
                },
                checkQuestion: function (event) {
                    event.preventDefault();
                },
                _prepareForm: function (event) {
                    var data = $('.answer-options').serializeJSON(),
                        $target = $(event.target),
                        $form = $target.is('form') ? $target : $(event.target.form),
                        $hidden = $('<input type="hidden" name="question-data" />').val(JSON.stringify(data));
                    $form.find('input[name="question-data"]').remove();
                    return $form.append($hidden);
                },
                toggle: function (event) {
                    var $el = $(event.target),
                        $chk = false;
                    if ($el.is('input.option-check')) {
                        return;
                    }
                    $chk = $el.closest('.answer-option').find('input.option-check');
                    if ($chk.is(':checkbox')) {
                        $chk[0].checked = !$chk[0].checked;
                    } else {
                        $chk[0].checked = true;
                    }
                }
            }
        });
    });

    $(document).ready(function () {
        $(document).ready(function () {
            var $content = $('.content-item-scrollable');
            $content.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $content.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 60,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            var $curriculum = $('.course-item-popup').find('.curriculum-scrollable');
            $curriculum.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $curriculum.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 0,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            setTimeout(function () {
                var $cs = $('body.course-item-popup').find('.curriculum-sections').parent();
                $cs.scrollTo($cs.find('.course-item.current'), 100);
            }, 300);

            /////$('.course-item-popup').find('#learn-press-course-curriculum').addClass('scrollbar-light').scrollbar({scrollx: false});

            $('body').css('opacity', 1);
        });
        return;
        Vue.component('LP_Quiz', {
            template: '#content-item-quiz',
            data: function () {
                return {
                    paged: 1,
                    term: '',
                    hasItems: false,
                    selected: []
                }
            },
            watch: {
                show: function (value) {
                }
            }
        });
        window.LP.$courseXYZ = new Vue({
            el: '#learn-press-course',
            data: {
                show: false,
                term: '',
                postType: '',
                callbacks: {}
            },
            methods: {
                nextQuestion: function (event) {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                prevQuestion: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                redoQuiz: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                startQuiz: function () {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                },
                completeItem: function (event) {
                    event.preventDefault();
                    var $form = this._prepareForm(event);
                    $form.submit();
                    return false;
                },
                _prepareForm: function (event) {
                    var data = $('.answer-options').serializeJSON(),
                        $target = $(event.target),
                        $form = $target.is('form') ? $target : $(event.target.form),
                        $hidden = $('<input type="hidden" name="question-data" />').val(JSON.stringify(data));
                    $form.find('input[name="question-data"]').remove();
                    return $form.append($hidden);
                },
                toggle: function (event) {
                    var $el = $(event.target),
                        $chk = false;
                    if ($el.is('input.option-check')) {
                        return;
                    }
                    $chk = $el.closest('.answer-option').find('input.option-check');
                    if ($chk.is(':checkbox')) {
                        $chk[0].checked = !$chk[0].checked;
                    } else {
                        $chk[0].checked = true;
                    }
                }
            }
        });
        /*Vue.component('learn-press-modal-search-items', {
         template: '#learn-press-modal-search-items',
         data: function () {
         return {
         paged: 1,
         term: '',
         hasItems: false,
         selected: []
         }
         },
         watch: {
         show: function (value) {
         if (value) {
         $(this.$refs.search).focus();
         }
         }
         },
         props: ['postType', 'context', 'contextId', 'show', 'callbacks'],
         created: function () {
         },
         methods: {
         doSearch: function (e) {
         this.term = e.target.value;
         this.paged = 1;
         this.search();
         },
         search: _.debounce(function (term) {
         var that = this;
         Vue.http.post(
         window.location.href, {
         type: this.postType,
         context: this.context,
         context_id: this.contextId,
         term: term || this.term,
         paged: this.paged,
         'lp-ajax': 'modal-search-items'
         }, {
         emulateJSON: true,
         params: {}
         }
         ).then(function (response) {
         var result = LP.parseJSON(response.body);
         that.hasItems = !!_.size(result.items);

         $(that.$el).find('.search-results').html(result.html).find('input[type="checkbox"]').each(function () {
         var id = parseInt($(this).val());
         if (_.indexOf(that.selected, id) >= 0) {
         this.checked = true;
         }
         });
         _.debounce(function () {
         $(that.$el).find('.search-nav').html(result.nav).find('a, span').addClass('button').filter('span').addClass('disabled');
         }, 10)();
         });
         }, 500),
         loadPage: function (e) {
         e.preventDefault();
         var $button = $(e.target);
         if ($button.is('span')) {
         return;
         }
         if ($button.hasClass('next')) {
         this.paged++;
         } else if ($button.hasClass('prev')) {
         this.paged--;
         } else {
         var paged = $button.html();
         this.paged = parseInt(paged);
         }
         this.search();
         },
         selectItem: function (e) {
         var $select = $(e.target).closest('li'),
         $chk = $select.find('input[type="checkbox"]'),
         id = parseInt($chk.val()),
         pos = _.indexOf(this.selected, id);

         if ($chk.is(':checked')) {
         if (pos === -1) {
         this.selected.push(id);
         }
         } else {
         if (pos >= 0) {
         this.selected.splice(pos, 1);
         }
         }
         },
         addItems:function(){
         var close = true;
         if(this.callbacks && this.callbacks.addItems){
         this.callbacks.addItems.call(this);
         }
         $(document).triggerHandler('learn-press/add-order-items', this.selected);
         },
         close: function () {
         this.$emit('close');
         }
         }
         });
         */

    });
    $('htmlv').one('click.xxxx', '.course-item', function (e) {
        var $target = $(e.target),
            $a = $target.closest('.course-item').find('a');

        jQuery.ajax({
            url: $a.attr('href'),
            success: function (res) {
                var $dom = $(document.createElement("html"));
                $dom[0].innerHTML = res;

                var $head = $dom.find("head"),
                    $body = $dom.find("body");

                var $oldHead = $('head'),
                    $oldBody = $('body');

                jQuery('html').append($head).append($body).load(function () {
                    alert();
                });
                setTimeout(function ($a, $b) {
                    $a.remove();
                    $b.remove();
                }, 300, $oldHead, $oldBody)
                //$oldHead.remove();
                //$oldBody.remove();

                LP.setUrl($a.attr('href'))
            }
        });
        e.preventDefault();
    })


})(jQuery, LP, _, Vue, Vuex);