<?php
/**
 * Question answer item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-answer-item">
    <tr class="lp-list-option lp-row"
        :data-answer-id="answer.question_answer_id"
        :class="answerClass(answer.value)"
        :data-id="answer.value">
        <td class="lp-column lp-column-sort"><i class="fa fa-bars"></i></td>
        <td class="lp-column lp-column-order">{{index +1}}</td>
        <td class="lp-column lp-column-answer_text">
            <input type="text" v-model="answer.text"
                   @blur="updateAnswerTitle"/>
        </td>
        <td class="lp-column lp-column-answer_correct lp-answer-check">
            <template v-if="isTrueOrFalse">
                <input type="radio" :checked="answer.is_true === 'yes'" :value="answer.value"
                       name="learnpress-answers-question[]" @change="changeCorrectAnswer">
            </template>
            <template v-else>
                <input type="checkbox" :checked="answer.is_true === 'yes'" :value="answer.value"
                       name="learnpress-answers-question[]">
            </template>
        </td>
        <td class="lp-column lp-column-actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn lp-btn-remove" v-if="!(isTrueOrFalse || disableDeleteAnswer)">
                <a class="lp-btn-icon dashicons dashicons-trash"
                   @click="deleteQuestionAnswer"></a>
            </div>
        </td>
    </tr>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-question-answer-item', {
            template: '#tmpl-lp-question-answer-item',
            props: ['questionId', 'answer', 'index', 'isTrueOrFalse', 'disableDeleteAnswer'],
            methods: {
                answerClass: function (answer) {
                    return 'lp-list-option-' + answer;
                },
                changeCorrectAnswer: function (e) {
                    var question = {'id': this.question.id, 'value': e.target.value};
                    $store.dispatch('lqs/changeCorrectAnswer', question);
                },
                updateAnswerTitle: function () {
                    var request = {
                        'action': 'update-title',
                        'answer': this.answer,
                        'questionId': this.questionId
                    };
                    $store.dispatch('lqs/updateQuestionAnswer', request);
                },
                deleteQuestionAnswer: function () {

                    var request = {
                        'questionId': this.questionId,
                        'answerId': this.answer.question_answer_id
                    };

                    $store.dispatch('lqs/deleteQuestionAnswer', request);

                }
            }
        });

    })(Vue, LP_Quiz_Store);
</script>
