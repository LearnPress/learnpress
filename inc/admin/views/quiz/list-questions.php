<?php
/**
 * Template list quiz questions.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/question-item' );
learn_press_admin_view( 'quiz/question-settings' );
?>

<script type="text/x-template" id="tmpl-lp-list-quiz-questions">
    <draggable v-model="listQuestions" :element="'tbody'" :options="optionDraggable" @end="updateSortQuestions">
        <template v-for="(question, index) in listQuestions">
            <lp-quiz-question-item :question="question" :index="index"></lp-quiz-question-item>
            <lp-quiz-question-settings :question="question" :index="index"></lp-quiz-question-settings>
        </template>
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