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
    <div class="question-item" :item-id="question.id">
        <lp-quiz-question-actions :question="question" :index="index"></lp-quiz-question-actions>
        <lp-quiz-question-settings :question="question" :index="index"></lp-quiz-question-settings>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-quiz-question-item', {
            template: '#tmpl-lp-quiz-question-item',
            props: ['question', 'index']
        });

    })(Vue, LP_Quiz_Store);
</script>
