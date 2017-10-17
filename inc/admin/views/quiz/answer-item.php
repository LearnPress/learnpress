<?php
/**
 * Question answer item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-answer-item">
    <tr class="answer-item"
        :data-answer-id="answer.question_answer_id"
        :class="answerClass(answer.value)"
        :data-id="answer.value">
        <td class="lp-column lp-column-sort"><i class="fa fa-bars"></i></td>
        <td class="lp-column lp-column-order">{{index +1}}</td>
        <td class="lp-column lp-column-answer_text">
            <input type="text" v-model="answer.text"
                   @keyup.enter="updateAnswerTitle"
                   @blur="updateAnswerTitle"/>
        </td>
        <td class="lp-column lp-column-answer_correct lp-answer-check">
            <template v-if="isTrueOrFalse || isSingleChoice">
                <input type="radio" :checked="isTrue" :value="answer.value" :name="name"
                       @change="changeCorrect">
            </template>
            <template v-else>
                <input type="checkbox" :checked="isTrue" :value="answer.value" :name="name"
                       @change="changeCorrect">
            </template>
        </td>
        <td class="lp-column lp-column-actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn lp-btn-remove" v-if="deletable">
                <a class="lp-btn-icon dashicons dashicons-trash" @click="deleteQuestionAnswer"></a>
            </div>
        </td>
    </tr>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-question-answer-item', {
            template: '#tmpl-lp-question-answer-item',
            props: ['question', 'answer', 'index', 'isTrueOrFalse', 'isSingleChoice', 'disableDeleteAnswer'],
            computed: {
                isTrue: function () {
                    return this.answer.is_true === 'yes' ? 'checked' : '';
                },
                name: function () {
                    return 'answer_question[' + this.question.id + ']'
                },
                numberCorrect: function () {
                    var correct = 0;
                    this.question.answers.forEach(function (answer) {
                        if (answer.is_true === 'yes') {
                            correct += 1;
                        }
                    });
                    return correct;
                },
                deletable: function () {
                    if ((this.answer.is_true === 'yes' && this.numberCorrect === 1) || this.isTrueOrFalse || this.disableDeleteAnswer) {
                        return false;
                    } else {
                        return true;
                    }
                }
            },
            methods: {
                answerClass: function (answer) {
                    return 'lp-list-option-' + answer;
                },
                changeCorrect: function (e) {
                    this.answer.is_true = (e.target.checked) ? 'yes' : '';
                    this.$emit('changeCorrect', this.answer);
                },
                updateAnswerTitle: function () {
                    var request = {
                        'action': 'update-title',
                        'answer': this.answer,
                        'questionId': this.question.id
                    };
                    $store.dispatch('lqs/updateQuestionAnswer', request);
                },
                deleteQuestionAnswer: function () {

                    var request = {
                        'questionId': this.question.id,
                        'answerId': this.answer.question_answer_id
                    };

                    $store.dispatch('lqs/deleteQuestionAnswer', request);

                }
            }
        });

    })(Vue, LP_Quiz_Store);
</script>
