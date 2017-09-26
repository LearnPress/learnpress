<?php
/**
 * Template list quiz questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question' );
?>

<script type="text/x-template" id="tmpl-lp-list-quiz-questions">
    <draggable v-model="listQuestions" :element="'tbody'">
        <lp-quiz-question-item v-for="(question, index) in listQuestions" :question="question"
                               :key="index"></lp-quiz-question-item>
    </draggable>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-list-quiz-questions', {
            template: '#tmpl-lp-list-quiz-questions',
            computed: {
                listQuestions: function () {
                    return $store.getters['lqs/listQuestions'];
                }
            }
        });

    })(Vue, LP_Quiz_Store)
</script>