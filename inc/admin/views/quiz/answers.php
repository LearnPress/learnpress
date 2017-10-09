<?php
/**
 * Question answers template.
 *
 * @since 3.0.0
 */
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
                    <tr v-for="(answer, index) in question.answers.options" class="lp-list-option lp-row"
                        :class="answerClass(answer.value)"
                        :data-id="answer.value">
                        <td class="lp-column lp-column-sort"><i class="fa fa-bars"></i></td>
                        <td class="lp-column lp-column-order">{{index +1}}</td>
                        <td class="lp-column lp-column-answer_text">{{answer.text}}</td>
                        <td class="lp-column lp-column-answer_correct lp-answer-check">
                            <template v-if="question.type.key === 'true_or_false'">
                                <input type="radio" :checked="answer.is_true === 'yes'" :value="answer.value"
                                       name="learnpress-answers-question[]" @change="changeCorrectAnswer">
                            </template>
                            <template v-else>
                                <input type="checkbox" :checked="answer.is_true === 'yes'" :value="answer.value"
                                       name="learnpress-answers-question[]">
                            </template>
                        </td>
                        <td class="lp-column lp-column-actions lp-toolbar-buttons">
                            <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
                                <a class="lp-btn-icon dashicons dashicons-trash learn-press-tooltip"></a>
                            </div>
                        </td>
                    </tr>
                </draggable>
            </table>
        </div>
        <p class="question-button-actions" v-if="question.type.key !== 'true_or_false'">
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
            computed: {},
            methods: {
                headingClass: function (heading) {
                    return 'lp-column-heading-' + heading;
                },
                answerClass: function (answer) {
                    return 'lp-list-option-' + answer;
                },
                answersChecked: function (answer) {
                    return (answer === 'yes') ? 'checked' : '';
                },
                changeCorrectAnswer: function (e) {
                    var question = {'id': this.question.id, 'value': e.target.value};
                    $store.dispatch('lqs/changeCorrectAnswer', question);
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
