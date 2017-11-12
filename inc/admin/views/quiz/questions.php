<?php
/**
 * Admin Quiz Editor: List questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-questions">
    <draggable :list="questions" class="main" :options="{handle: '.fa-bars'}" @end="sort">
        <lp-quiz-question-item v-for="(question, index) in questions" :question="question" :index="index"
                               :key="index"></lp-quiz-question-item>
    </draggable>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-quiz-questions', {
            template: '#tmpl-lp-quiz-questions',
            computed: {
                // list quiz questions
                questions: function () {
                    return $store.getters['lqs/listQuestions'];
                }
            },
            methods: {
                // sort questions
                sort: function () {
                    var order = [];
                    this.questions.forEach(function (question, index) {
                        order.push(parseInt(question.id));
                    });

                    $store.dispatch('lqs/updateQuestionsOrder', order);
                }
            }
        });

    })(Vue, LP_Quiz_Store)
</script>