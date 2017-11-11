<?php
/**
 * Question answer item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-answer-option">
    <tr class="answer-item" :data-answer-id="answer.question_answer_id">
        <td class="lp-column lp-column-sort"><i class="fa fa-bars"></i></td>
        <td class="lp-column lp-column-order">{{index +1}}</td>
        <td class="lp-column lp-column-answer_text">
            <input type="text" v-model="answer.text"
                   @change="changeTitle" @keyup.enter="updateTitle" @blur="updateTitle"/>
        </td>
        <td class="lp-column lp-column-answer_correct lp-answer-check">
            <input :type="radio ? 'radio' : 'checkbox'" :checked="correct" :value="answer.value" :name="name"
                   @change="changeCorrect">

        </td>
        <td class="lp-column lp-column-actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn lp-btn-remove" v-if="deletable">
                <a class="lp-btn-icon dashicons dashicons-trash" @click="deleteAnswer"></a>
            </div>
        </td>
    </tr>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-quiz-question-answer-option', {
            template: '#tmpl-lp-quiz-question-answer-option',
            props: ['question', 'answer', 'index'],
            data: function () {
                return {
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
                    return ((this.answer.is_true === 'yes' && this.numberCorrect === 1) || (this.question.type.key === 'true_or_false') || this.question.answers.length < 2);
                }
            },
            methods: {
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
                        var request = {
                            'question_id': this.question.id,
                            'answer': this.answer
                        };
                        $store.dispatch('lqs/updateQuestionAnswerTitle', request);


                    }
                },
                // deletable answer
                deleteAnswer: function () {

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
