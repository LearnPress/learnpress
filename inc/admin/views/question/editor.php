<?php
/**
 * Admin question editor: editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/actions' );
learn_press_admin_view( 'question/answer' );
?>

<script type="text/x-template" id="tmpl-lp-question-editor">

    <div id="lp-admin-question-editor" class="learn-press-box-data">

        <lp-question-actions :type="type" @changeType="changeType"></lp-question-actions>

        <div class="lp-box-data-content">
            <table class="list-question-answers">
                <thead>
                <tr>
                    <th class="sort"></th>
                    <th class="order"></th>
                    <th class="answer_text"><?php __( 'Answer Text', 'learnpress' ); ?></th>
                    <th class="answer_correct"><?php __( 'Is Correct?', 'learnpress' ); ?></th>
                    <th class="actions"></th>
                </tr>
                </thead>
                <draggable :list="answers" :element="'tbody'" @end="sort">
                    <lp-question-answer v-for="(answer, index) in answers" :key="index" :index="index" :type="type"
                                        :radio="radio" :number="number" :answer="answer"
                                        @updateTitle="updateTitle"
                                        @changeCorrect="changeCorrect"
                                        @deleteAnswer="deleteAnswer"></lp-question-answer>
                </draggable>
            </table>
            <p class="add-answer" v-if="addable">
                <button class="button add-question-option-button" type="button"
                        @click="newAnswer"><?php esc_html_e( 'Add option', 'learnpress' ); ?></button>
            </p>
        </div>
    </div>

</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-question-editor', {
            template: '#tmpl-lp-question-editor',
            computed: {
                // list answers
                answers: function () {
                    return $store.getters['answers'];
                },
                // question type key
                type: function () {
                    return $store.getters['type']['key'];
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
                // draft new question
                draftQuestion: function () {
                    if (this.draft) {
                        $store.dispatch('draftQuestion', {
                            title: $('input[name=post_title]').val(),
                            content: $('textarea[name=content]').val()
                        });
                    }
                },
                changeType: function (type) {
                    // create draft quiz if auto draft
                    $store.dispatch('changeQuestionType', {
                        question: {
                            title: $('input[name=post_title]').val(),
                            content: $('textarea[name=content]').val()
                        },
                        type: type
                    });
                },
                // sort answer options
                sort: function () {

                    // sort answer
                    var order = [];
                    this.answers.forEach(function (answer) {
                        order.push(parseInt(answer.question_answer_id));
                    });
                    $store.dispatch('updateAnswersOrder', order);
                },
                // change answer title
                updateTitle: function (answer) {
                    // update title
                    $store.dispatch('updateAnswerTitle', answer);
                },
                // change correct answer
                changeCorrect: function (correct) {
                    // update correct
                    $store.dispatch('updateCorrectAnswer', {
                        question: {
                            title: $('input[name=post_title]').val(),
                            content: $('textarea[name=content]').val()
                        },
                        correct: correct
                    });
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
