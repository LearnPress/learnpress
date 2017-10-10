<?php
/**
 * Question answers template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/answer-item' );
?>

<script type="text/x-template" id="tmpl-lp-question-answers">
    <div class="quiz-question-data">
        <div class="lp-list-questions">
            <table class="lp-list-options">
                <thead>
                <tr>
                    <th v-for="(heading, key) in question.answers.heading" class="lp-column-heading"
                        :class="headingClass(key)">
                        {{heading}}
                    </th>
                </tr>
                </thead>
                <draggable :list="question.answers.options" :element="'tbody'" @end="sortQuestionAnswers">
                    <lp-question-answer-item v-for="(answer, index) in question.answers.options" :key="index" :questionId="question.id" :answer="answer" :index="index" :isTrueOrFalse="isTrueOrFalse" :disableDeleteAnswer="disableDeleteAnswer"></lp-question-answer-item>
                </draggable>
            </table>
        </div>
        <p class="question-button-actions" v-if="!isTrueOrFalse">
            <button class="button add-question-option-button" type="button"
                    @click="addQuestionAnswer"><?php esc_html_e( 'Add option' ) ?></button>
        </p>
    </div>
</script>

<script>
    (function (Vue, $store) {
        Vue.component('lp-question-answers', {
            template: '#tmpl-lp-question-answers',
            props: ['question'],
            computed: {
                isTrueOrFalse: function () {
                    return this.question.type.key === 'true_or_false';
                },
                disableDeleteAnswer: function () {
                    return this.question.answers.options.length < 3;
                }
            },
            methods: {
                headingClass: function (heading) {
                    return 'lp-column-heading-' + heading;
                },
                addQuestionAnswer: function () {
                    $store.dispatch('lqs/addQuestionAnswer', this.question);
                },
                sortQuestionAnswers: function () {
                    var orders = [];
                    this.question.answers.options.forEach(function (option, index) {
                        orders.push(parseInt(option.question_answer_id));
                    });
                    $store.dispatch('lqs/updateOrderQuestionAnswers', orders);
                }
            }
        })
    })(Vue, LP_Quiz_Store);
</script>
