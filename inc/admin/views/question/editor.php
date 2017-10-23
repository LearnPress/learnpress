<?php
/**
 * Quiz editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/answers' );
learn_press_admin_view( 'question/meta' );
?>


<script type="text/x-template" id="tmpl-lp-quiz-editor">
    <div id="quiz-editor-v2" class="learn-press-box-data">
        <div class="lp-box-data-content">
            <div class="lp-list-questions">
                <div class="main">
                    <div class="question-item">
                        <div class="question-settings table-row">
                            <lp-question-answers v-for="(question, index) in listQuestions" :question="question"
                                                 :key="index"></lp-question-answers>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-quiz-editor', {
            template: '#tmpl-lp-quiz-editor',
            computed: {
                listQuestions: function () {
                    return $store.getters['lqs/listQuestions'];
                }
            }

        })

    })(Vue, LP_Quiz_Store, jQuery);
</script>
