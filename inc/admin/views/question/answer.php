<?php
/**
 * Admin question editor: question answer template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/option' );
?>

<script type="text/x-template" id="tmpl-lp-question-answer">
    <div class="lp-box-data-content">
        <table class="list-question-answers">
            <thead>
            <tr>
                <th class="sort"></th>
                <th class="order"></th>
                <th class="answer_text"><?php _e( 'Answer Text', 'learnpress' ); ?></th>
                <th class="answer_correct"><?php _e( 'Is Correct?', 'learnpress' ); ?></th>
                <th class="actions"></th>
            </tr>
            </thead>
            <draggable :list="answers" :element="'tbody'" @end="sort">
                <lp-question-answer-option v-for="(answer, index) in answers" :key="index" :index="index" :type="type"
                                           :radio="radio" :number="number" :answer="answer"
                                           @updateTitle="updateTitle"
                                           @changeCorrect="changeCorrect"
                                           @deleteAnswer="deleteAnswer"></lp-question-answer-option>
            </draggable>
        </table>
        <p class="add-answer" v-if="addable">
            <button class="button add-question-option-button" type="button"
                    @click="newAnswer"><?php esc_html_e( 'Add option', 'learnpress' ); ?></button>
        </p>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-question-answer', {
            template: '#tmpl-lp-question-answer',
            props: ['type'],
            computed: {
                // list answers
                answers: function () {
                    return $store.getters['answers'];
                },
                // check type radio answer type
                radio: function () {
                    return this.type === 'true_or_false' || this.type === 'single_choice';
                },
                // number answer
                number: function () {
                    return this.answers.length;
                },
                // addable new answer
                addable: function () {
                    return this.type !== 'true_or_false';
                },
                // question status
                status: function () {
                    return $store.getters['status'];
                },
                // get draft status
                draft: function () {
                    return $store.getters['autoDraft'];
                }
            },
            methods: {
                // sort answer options
                sort: function () {
                    if (!this.draft) {
                        // sort answer
                        var order = [];
                        this.answers.forEach(function (answer) {
                            order.push(parseInt(answer.question_answer_id));
                        });
                        $store.dispatch('updateAnswersOrder', order);
                    }
                },
                // change answer title
                updateTitle: function (answer) {
                    if (!this.draft) {
                        // update title
                        $store.dispatch('updateAnswerTitle', answer);
                    }
                },
                // change correct answer
                changeCorrect: function (correct) {
                    if (!this.draft) {
//                    // update correct
                        $store.dispatch('updateCorrectAnswer', correct);
                    }
                },
                // delete answer
                deleteAnswer: function (answer) {
                    $store.dispatch('deleteAnswer', answer);
                },
                // new answer option
                newAnswer: function () {
                    // new answer
                    if (this.status === 'successful') {
                        $store.dispatch('newAnswer');
                    }
                }
            }
        })
    })(Vue, LP_Question_Store, jQuery);

</script>
