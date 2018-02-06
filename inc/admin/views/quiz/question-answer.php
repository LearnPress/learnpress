<?php
/**
 * Question answers template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-answer-option' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-answers">
    <div class="quiz-question-data">
        <div class="lp-list-questions">
            <table class="lp-list-options">
                <thead>
                <tr>
                    <th class="sort"></th>
                    <th class="order"></th>
                    <th class="answer-text"><?php esc_html_e( 'Answer Text', 'learnpress' ); ?></th>
                    <th class="answer-correct"><?php esc_html_e( 'Is Correct?', 'learnpress' ); ?></th>
                    <th class="actions"></th>
                </tr>
                </thead>
                <tbody>
                <lp-quiz-question-answer-option v-for="(answer, index) in question.answers"
                                                :question="question" :answer="answer" :index="index" :key="index"
                                                @changeCorrect="changeCorrect"
                                                @nav="navItem"></lp-quiz-question-answer-option>
                </tbody>
            </table>
        </div>
        <p class="question-button-actions" v-if="addableAnswer">
            <button class="button add-question-option-button" type="button"
                    @click="newAnswer"><?php esc_html_e( 'Add option', 'learnpress' ); ?></button>
        </p>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {
        Vue.component('lp-quiz-question-answers', {
            template: '#tmpl-lp-quiz-question-answers',
            props: ['question'],
            computed: {
                // addable answer option
                addableAnswer: function () {
                    return !(String(this.question.type.key) === 'true_or_false');
                }
            },
            mounted: function () {
                var _self = this;
                setTimeout(function () {
                    var $el = $('.quiz-question-data .lp-list-questions>.lp-list-options tbody');
                    $el.sortable({
                        handle: '.sort',
                        axis: 'y',
                        update: function () {
                            _self.sort();
                        }
                    });
                }, 1000)

            },
            methods: {
                // sort answer options
                sort: function () {

                    var _items = $('.question-item[data-item-id="' + this.question.id + '"] .quiz-question-data .lp-list-questions>.lp-list-options tbody tr');
                    var _order = [];
                    _items.each(function (index, item) {
                        $(item).find('.order').text(index + 1);
                        _order.push($(item).data('answer-id'));
                    });

                    $store.dispatch('lqs/updateQuestionAnswersOrder', {
                        question_id: this.question.id,
                        order: _order
                    });
                },
                // change correct answer
                changeCorrect: function (correct) {
                    $store.dispatch('lqs/updateQuestionCorrectAnswer', {
                        question_id: this.question.id,
                        correct: correct
                    });
                },
                // new answer option
                newAnswer: function () {
                    $store.dispatch('lqs/newQuestionAnswer', this.question.id);
                },
                // navigation course items
                navItem: function (payload) {

                    var keyCode = payload.key,
                        order = payload.order;

                    if (keyCode === 38 && order > 0) {
                        this.nav(order - 1);
                    }
                    if (keyCode === 40 || keyCode === 13) {
                        if (order === this.question.answers.length) {
                            // code
                        } else {
                            this.nav(order + 1);
                        }
                    }

                },
                // focus item
                nav: function (position) {
                    var element = 'div[data-item-id=' + this.question.id + '] tr[data-order-answer=' + position + ']';
                    ($(element).find('.answer-text input')).focus();
                }
            }
        })
    })(Vue, LP_Quiz_Store, jQuery);
</script>
