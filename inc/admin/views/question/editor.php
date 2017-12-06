<?php
/**
 * Admin question editor: editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'question/actions' );
learn_press_admin_view( 'question/answer' );
?>

<script type="text/x-template" id="tmpl-lp-question-editor">
    <div id="admin-editor-lp_question" class="lp-admin-editor learn-press-box-data">
        <lp-question-actions :type="type" @changeType="changeType"></lp-question-actions>
        <lp-question-answer :type="type"></lp-question-answer>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-question-editor', {
            template: '#tmpl-lp-question-editor',
            computed: {
                // question type key
                type: function () {
                    return $store.getters['type']['key'];
                }
            },
            methods: {
                changeType: function (type) {
                    // create draft quiz if auto draft
                    $store.dispatch('changeQuestionType', {
                        question: {
                            title: $('input[name=post_title]').val(),
                            content: $('textarea[name=content]').val()
                        },
                        type: type
                    });
                }
            }
        });

    })(Vue, LP_Question_Store, jQuery);
</script>
