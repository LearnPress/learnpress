<?php
/**
 * Template list quiz questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-item' );
?>


<script type="text/x-template" id="tmpl-lp-list-quiz-questions">
    <draggable :list="listQuestions" class="main" :options="{handle: '.fa-bars'}" :element="'div'"
               @end="sortQuestions">
        <lp-question-item v-for="(question, index) in listQuestions" :question="question" :index="index"
                          :key="index"></lp-question-item>
    </draggable>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-list-quiz-questions', {
            template: '#tmpl-lp-list-quiz-questions',
            computed: {
                listQuestions: function () {
                    return $store.getters['lqs/listQuestions'];
                },
                questionsOrder: function () {
                    return $store.getters['lqs/questionsOrder'];
                }
            },
            methods: {
                sortQuestions: function () {
                    var orders = [];
                    this.listQuestions.forEach(function (question, index) {
                        orders.push(parseInt(question.id));
                    });

                    $store.dispatch('lqs/updateOrderQuestions', orders);
                }
            }
        });

    })(Vue, LP_Quiz_Store)
</script>