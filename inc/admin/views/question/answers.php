<?php
/**
 * Question answers template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/answer-item' );

?>

<script type="text/x-template" id="tmpl-lp-question-answers">
    <div class="quiz-question-data">
        <div class="lp-list-questions">
            <table class="lp-list-options">
                <thead>
                <tr>
                    <th class="lp-column-heading lp-column-heading-sort"></th>
                    <th class="lp-column-heading lp-column-heading-order"></th>
                    <th class="lp-column-heading lp-column-heading-answer_text"><?php esc_html_e( 'Answer Text', 'learnpress' ); ?></th>
                    <th class="lp-column-heading lp-column-heading-answer_correct"><?php esc_html_e( 'Is Correct?', 'learnpress' ); ?></th>
                    <th class="lp-column-heading lp-column-heading-actions"></th>
                </tr>
                </thead>
                <draggable :list="question.answers" :element="'tbody'" @end="sortQuestionAnswers">
                    <lp-question-answer-item v-for="(answer, index) in question.answers" :key="index"
                                             :question="question" :answer="answer" :index="index"
                                             :isTrueOrFalse="isTrueOrFalse" :isSingleChoice="isSingleChoice"
                                             :disableDeleteAnswer="disableDeleteAnswer"
                                             @changeCorrect="changeCorrect"></lp-question-answer-item>
                </draggable>
            </table>
        </div>
        <p class="question-button-actions" v-if="!isTrueOrFalse">
            <button class="button add-question-option-button" type="button"
                    @click="addQuestionAnswer"><?php esc_html_e( 'Add option' ) ?></button>
        </p>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {
        Vue.component('lp-question-answers', {
            template: '#tmpl-lp-question-answers',
            props: ['question'],
            computed: {
                isTrueOrFalse: function () {
                    return this.question.type.key === 'true_or_false';
                },
                isSingleChoice: function () {
                    return this.question.type.key === 'single_choice';
                },
                disableDeleteAnswer: function () {
                    return this.question.answers.length < 3;
                }
            },
            methods: {
                headingClass: function (heading) {
                    return 'lp-column-heading-' + heading;
                },
                addQuestionAnswer: function () {

                    var request = {
                        'questionId': this.question.id,
                        'answer': {
                            text: $store.getters['i18n/all'].option + ' ' + (this.question.answers.length + 1),
                            isTrue: '',
                            order: this.question.answers.length + 1,
                            value: $store.getters['i18n/all'].unique
                        }
                    };

                    $store.dispatch('lqs/addQuestionAnswer', request);
                },
                sortQuestionAnswers: function () {
                    var orders = [];

                    this.question.answers.forEach(function (option, index) {
                        orders.push(parseInt(option.question_answer_id));
                    });
                    $store.dispatch('lqs/updateOrderQuestionAnswers', orders);
                },
                changeCorrect: function (correctAnswer) {
                    var request = {
                        'question': this.question,
                        'correctAnswer': correctAnswer
                    };
                    $store.dispatch('lqs/updateQuestionCorrectAnswer', request);
                }
            }
        })
    })(Vue, LP_Quiz_Store);
</script>
