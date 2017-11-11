<?php
/**
 * Admin question editor: editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/answer' );
?>

<script type="text/x-template" id="tmpl-lp-question-editor">

    <div id="lp-admin-question-editor" class="learn-press-box-data">
        <div class="lp-box-data-head lp-row">
            <h3 class="heading"><?php esc_html_e( 'Question Answers', 'learnpress' ); ?></h3>
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn question-actions">
                    <div class="question-types">
                        <a href="" class="lp-btn-icon dashicons dashicons-editor-help"></a>
                        <ul>
                            <li v-for="(type, key) in types" :data-type="key" :class="active(key)">
                                <a href="" @click.prevent="changeType(key)">{{type}}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="lp-box-data-content">
            <table class="list-question-answers">
                <thead>
                <tr>
                    <th v-for="(heading, key) in headings" :class="key">{{heading}}</th>
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
                headings: function () {
                    return $store.getters['headings'];
                },
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
                // answer addable
                addable: function () {
                    return this.type !== 'true_or_false' && $store.getters['supportAnswerOption'];
                },
                // question status
                status: function () {
                    return $store.getters['status'];
                },
                // all question types
                types: function () {
                    return $store.getters['types']
                }
            },
            methods: {
                // check question type active
                active: function (type) {
                    return this.type === type ? 'active' : '';
                },
                changeType: function (type) {
                    if (this.type !== type) {
                        $store.dispatch('changeQuestionType', type);
                    }
                },
                // sort answer options
                sort: function () {
                    var orders = [];

                    this.answers.forEach(function (answer) {
                        orders.push(parseInt(answer.question_answer_id));
                    });

                    $store.dispatch('updateAnswersOrder', orders);
                },
                changeCorrect: function (correct) {
                    $store.dispatch('updateCorrectAnswer', correct);
                },
                newAnswer: function () {
                    if (this.status === 'successful') {
                        $store.dispatch('newAnswer');
                    }
                }
            }

        })

    })(Vue, LP_Question_Store);
</script>
