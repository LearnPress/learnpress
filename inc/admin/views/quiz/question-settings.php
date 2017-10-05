<?php
/**
 * Question template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/answers' );
learn_press_admin_view( 'quiz/options' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-settings">
    <tr class="edit-inline" :item-id="index" :class="question.open ? 'hide-if-js' : ''" :data-item="dataItem">
        <td colspan="5">
            <lp-question-answers :question="question"></lp-question-answers>
            <lp-question-options :question="question"></lp-question-options>
        </td>
    </tr>
</script>


<script>
    (function (Vue, $store) {
        Vue.component('lp-quiz-question-settings', {
            template: '#tmpl-lp-quiz-question-settings',
            props: ['question', 'index'],
            computed: {
                dataItem: function () {
                    return 'index-' + this.index;
                }
            }
        })
    })(Vue, LP_Quiz_Store)
</script>