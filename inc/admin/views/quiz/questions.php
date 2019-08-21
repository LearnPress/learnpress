<?php
/**
 * Admin Quiz Editor: List questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-questions">
    <div class="main">
        <lp-quiz-question-item v-for="(question, index) in questions" :question="question" :index="index"
                               :key="index"></lp-quiz-question-item>
    </div>
</script>

<script type="text/javascript">
    jQuery(function ($) {
        var $Vue = window.$Vue || Vue;
        var $store = window.LP_Quiz_Store;

        $Vue.component('lp-quiz-questions', {
            template: '#tmpl-lp-quiz-questions',
            computed: {
                // list quiz questions
                questions: function () {
                    return $store.getters['lqs/listQuestions'];
                }
            },
            mounted: function () {
                var _self = this;
                setTimeout(function () {
                    var $el = $('.lp-list-questions .main');
                    $el.sortable({
                        handle: '.question-actions .sort',
                        axis: 'y',
                        update: function () {
                            _self.sort();
                        }
                    });
                }, 1000)

            },
            methods: {
                // sort questions
                sort: function () {
                    var _items = $('.lp-list-questions .main>div.question-item');
                    var _order = [];
                    _items.each(function (index, item) {
                        $(item).find('.question-actions .order').text(index + 1);
                        _order.push($(item).data('item-id'));
                    });

                    $store.dispatch('lqs/updateQuestionsOrder', _order);
                }
            }
        });

    })
</script>