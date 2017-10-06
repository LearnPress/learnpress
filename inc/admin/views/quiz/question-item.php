<?php
/**
 * Question template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-actions' );
learn_press_admin_view( 'quiz/question-settings' );
?>

<script type="text/x-template" id="tmpl-lp-question-item">
    <div class="question-item" :item-id="index">
        <lp-question-actions :question="question" :index="index"></lp-question-actions>
        <lp-question-settings :question="question" :index="index"></lp-question-settings>

    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-question-item', {
            template: '#tmpl-lp-question-item',
            props: ['question', 'index']
        });

    })(Vue, LP_Quiz_Store);
</script>
