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

        <lp-question-actions :type="type"></lp-question-actions>

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
                                        @changeCorrect="changeCorrect"></lp-question-answer>
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
    (function (Vue, $store) {

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
                }
            },
            methods: {
                // sort answer options
                sort: function () {
                    var order = [];

                    this.answers.forEach(function (answer) {
                        order.push(parseInt(answer.question_answer_id));
                    });


                    $store.dispatch('updateAnswersOrder', order);
                },
                // change correct answer
                changeCorrect: function (correct) {
                    $store.dispatch('updateCorrectAnswer', correct);
                },
                // new answer option
                newAnswer: function () {
                    if (this.status === 'successful') {
                        $store.dispatch('newAnswer');
                    }
                }
            }

        })

    })(Vue, LP_Question_Store);
</script>
