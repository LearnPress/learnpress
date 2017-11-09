<?php
/**
 * Admin question editor: question answer template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-answer">
    <tr class="answer-item" :data-id="answer.value" :data-answer-id="answer.question_answer_id">
        <td class="sort"><i class="fa fa-bars"></i></td>
        <td class="order">{{answer.answer_order}}</td>
        <td class="answer-text">
            <input type="text" v-model="answer.text"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle"/>
        </td>
        <td class="answer-correct lp-answer-check">
            <input :type="radio ? 'radio' : 'checkbox'" :checked="correct ? 'checked' : ''" :value="answer.value"
                   :name="name"
                   @change="changeCorrect">
        </td>
        <td class="actions lp-toolbar-buttons">
            <div v-if="deletable" class="lp-toolbar-btn lp-btn-remove remove-answer">
                <a class="lp-btn-icon dashicons dashicons-trash" @click="deleteAnswer"></a>
            </div>
        </td>
    </tr>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-question-answer', {
            template: '#tmpl-lp-question-answer',
            props: ['answer', 'index', 'type', 'radio', 'number'],
            data: function () {
                return {
                    changed: false
                }
            },
            computed: {
                // check correct answer
                correct: function () {
                    return this.answer.is_true === 'yes';
                },
                // input correct form name
                name: function () {
                    return 'answer_question[' + $store.getters['id'] + ']'
                },
                // deletable answer
                deletable: function () {
                    return !(this.number < 3 || (this.correct && $store.getters['numberCorrect'] === 1 ) || this.type === 'true_or_false');
                }
            },
            methods: {
                changeTitle: function () {
                    this.changed = true;
                },
                updateTitle: function () {
                    if (this.changed) {
                        $store.dispatch('updateAnswerTitle', this.answer);
                    }
                },
                changeCorrect: function (e) {
                    this.answer.is_true = (e.target.checked) ? 'yes' : '';
                    this.$emit('changeCorrect', this.answer);
                },
                deleteAnswer: function () {
                    $store.dispatch('deleteAnswer', {
                        id: this.answer.question_answer_id,
                        order: this.answer.answer_order
                    });
                }
            }
        })
    })(Vue, LP_Question_Store);

</script>
