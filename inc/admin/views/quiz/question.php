<?php
/**
 * Admin Quiz Editor: Question item.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-actions' );
learn_press_admin_view( 'quiz/question-settings' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-item">
    <div :class="['question-item',question.type.key, isNew() ? 'empty-question' : '']" :data-item-id="question.id"
         :data-question-order="index">
        <lp-quiz-question-actions :question="question" :index="index" @nav="navItem"></lp-quiz-question-actions>
        <lp-quiz-question-settings :question="question" :index="index"></lp-quiz-question-settings>
    </div>
</script>

<script type="text/javascript">
    jQuery(function ($) {
        var $Vue = window.$Vue || Vue;
        var $store = window.LP_Quiz_Store;

        $Vue.component('lp-quiz-question-item', {
            template: '#tmpl-lp-quiz-question-item',
            props: ['question', 'index'],
            computed: {
                numberQuestions: function () {
                    return ($store.getters['lqs/listQuestions']).length;
                }
            },
            methods: {
                // navigation questions in quiz
                navItem: function (payload) {

                    var keyCode = payload.key,
                        order = payload.order;

                    if (keyCode === 38 && order !== 0) {
                        this.nav(order - 1);
                    }
                    if ((keyCode === 40 || keyCode === 13) && this.numberQuestions !== order) {
                        this.nav(order + 1);
                    }

                },
                // focus item
                nav: function (position) {
                    var element = 'div[data-question-order=' + position + ']';
                    ($(element).find('.name input')).focus();
                },
                isNew: function () {
                    return isNaN(this.question.id);
                }
            }
        });

    })
</script>
