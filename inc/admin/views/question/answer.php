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
        <table class="lp-list-options list-question-answers">
            <thead>
            <tr>
                <th class="sort"></th>
                <th class="order">#</th>
                <th class="answer-text"><?php _e( 'Answer Text', 'learnpress' ); ?></th>
                <th class="answer-correct"><?php _e( 'Correct?', 'learnpress' ); ?></th>
                <th class="actions"></th>
            </tr>
            </thead>
            <tbody>
            <!--            <draggable :list="answers" :element="'tbody'" @end="sort">-->
            <lp-question-answer-option v-for="(answer, index) in answers" :key="index" :index="index" :type="type"
                                       :radio="radio" :number="number" :answer="answer"
                                       @updateTitle="updateTitle"
                                       @changeCorrect="changeCorrect"
                                       @deleteAnswer="deleteAnswer"></lp-question-answer-option>
            </tbody>
            <!--            </draggable>-->
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
            props: ['type', 'answers'],
            computed: {
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
            created: function () {
                var _self = this;
                setTimeout(function () {
                    var $el = $('.list-question-answers tbody');
                    $el.sortable({
                        handle: '.sort',
                        axis: 'y',
                        helper: function (e, ui) {
                            var $tr = $('<tr />'),
                                $row = $(e.target).closest('tr');
                            $row.children().each(function () {
                                var $td = $(this).clone().width($(this).width())
                                $tr.append($td);
                            });

                            return $tr;
                        },
                        start: function () {

                        },
                        update: function () {
                            _self.sort();
                        }
                    });
                }, 1000)
            },
            methods: {
                // sort answer options
                sort: function () {
                    var _items = $('.list-question-answers tbody>tr.answer-option');
                    var _order = [];
                    _items.each(function (index, item) {
                        $(item).find('.order').text((index + 1) + '.');
                        _order.push($(item).data('answer-id'));
                    });

                    $store.dispatch('updateAnswersOrder', _order);

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
                        $store.dispatch('newAnswer', {
                            answer: {
                                value: LP.uniqueId(),
                                text: $store.getters.i18n.new_option_label
                            }
                        });
                    }
                }
            }
        })
    })(Vue, LP_Question_Store, jQuery);

</script>
