<?php
/**
 * Template list quiz questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-item' );
?>


<script type="text/x-template" id="tmpl-lp-list-quiz-questions">
    <draggable v-model="listQuestions" :options="optionDraggable" @end="updateSortQuestions" class="main">
        <lp-question-item v-for="(question, index) in listQuestions" :question="question" :index="index"
                          :key="index"></lp-question-item>
    </draggable>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-list-quiz-questions', {
            template: '#tmpl-lp-list-quiz-questions',
            computed: {
                listQuestions: function () {
                    return $store.getters['lqs/listQuestions'];
                },
                questionsOrder: function () {
                    return $store.getters['lqs/questionsOrder'];
                },
                optionDraggable: function () {
                    return {
                        handle: '.movable',
                        draggable: '.question-item'
                    }
                }
            },
            methods: {
                updateSortQuestions: function (event, b, c) {
                    var item = jQuery(event.item),
                        id = item.attr('data-item'),
                        sibling = item.siblings('[data-item="' + id + '"]');

                    sibling.insertAfter(item);

                    $store.dispatch('lqs/updateSortQuestions', this.questionsOrder);

                }
            }
        });

    })(Vue, LP_Quiz_Store)
</script>