<?php
/**
 * Question template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-answer' );
learn_press_admin_view( 'quiz/question-meta' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-settings">
    <div class="question-settings" :class="question.open ? 'table-row' : 'hide-if-js'">
        <lp-quiz-question-answers :question="question"></lp-quiz-question-answers>
        <lp-quiz-question-meta :question="question"></lp-quiz-question-meta>
    </div>
</script>


<script type="text/javascript">
    (function (Vue, $store) {
        Vue.component('lp-quiz-question-settings', {
            template: '#tmpl-lp-quiz-question-settings',
            props: ['question', 'index']
        })
    })(Vue, LP_Quiz_Store)
</script>