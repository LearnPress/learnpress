<?php
/**
 * Question answer item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-answer-option">
    <tr class="answer-option" :class="[isNew() ? 'empty-option' : '']" :data-answer-id="answer.question_answer_id"
        :data-order-answer="index">
        <td class="sort lp-sortable-handle"><?php learn_press_admin_view( 'svg-icon' ); ?></td>
        <td class="order">{{index +1}}.</td>
        <td class="answer-text">
            <input type="text" v-model="answer.text"
                   @change="changeTitle" @keyup.enter="updateTitle" @blur="updateTitle" @keyup="keyUp"/>
        </td>
        <td class="answer-correct lp-answer-check">
            <input :type="radio ? 'radio' : 'checkbox'" :checked="correct" :value="answer.value" :name="name"
                   @change="changeCorrect">
        </td>
        <td class="actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn lp-btn-remove" v-if="deletable">
                <a class="lp-btn-icon dashicons dashicons-trash" @click="deleteAnswer"></a>
            </div>
        </td>
    </tr>
</script>

<script type="text/javascript">
    jQuery(function ($) {
        var $Vue = window.$Vue || Vue;
        var $store = window.LP_Quiz_Store;

        $Vue.component('lp-quiz-question-answer-option', {
            template: '#tmpl-lp-quiz-question-answer-option',
            props: ['question', 'answer', 'index'],
            data: function () {
                return {
                    // origin answer text
                    text: this.answer.text,
                    changed: false
                }
            },
            computed: {
                // correct answer
                correct: function () {
                    return this.answer.is_true === 'yes';
                },
                // radio
                radio: function () {
                    var type = this.question.type.key;

                    return type === 'true_or_false' || type === 'single_choice';
                },
                // correct answer input name
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
                // deletable answer option
                deletable: function () {
                    return !((this.answer.is_true === 'yes' && this.numberCorrect === 1) || (this.question.type.key === 'true_or_false') || this.question.answers.length < 3);
                }
            },
            methods: {
                isNew: function () {
                    return isNaN(this.answer.question_answer_id)
                },
                changeCorrect: function (e) {
                    this.answer.is_true = (e.target.checked) ? 'yes' : '';
                    this.$emit('changeCorrect', this.answer);
                },
                // detect change answer title
                changeTitle: function () {
                    this.changed = true;
                },
                // update answer option title
                updateTitle: function () {
                    if (this.changed) {
                        $store.dispatch('lqs/updateQuestionAnswerTitle', {
                            question_id: this.question.id,
                            answer: this.answer
                        });
                    }
                },
                // deletable answer
                deleteAnswer: function () {
                    $store.dispatch('lqs/deleteQuestionAnswer', {
                        question_id: this.question.id,
                        answer_id: this.answer.question_answer_id
                    });
                },
                // navigation answer option items
                keyUp: function (event) {
                    var keyCode = event.keyCode;
                    // escape update answer option items text
                    if (keyCode === 27) {
                        this.answer.text = this.text;
                    } else {
                        this.$emit('nav', {key: event.keyCode, order: this.index});
                    }
                }
            }
        });

    })
</script>
