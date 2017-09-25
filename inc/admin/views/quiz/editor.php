<?php
/**
 * Quiz editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/list-questions' );
learn_press_admin_view( 'quiz/modal-choose-items' );
?>


<script type="text/x-template" id="tmpl-lp-quiz-editor">
    <div id="quiz-editor-v2" class="learn-press-box-data" :class="{'need-reload': !heartbeat}">
        <div class="lp-box-data-head">
            <h3><?php echo __( 'Questions', 'learnpress' ); ?></h3>
        </div>
        <div class="lp-box-data-content">

            <table class="lp-list-questions">
                <thead>
                <tr>
                    <th class="lp-column-sort"></th>
                    <th class="lp-column-order">#</th>
                    <th class="lp-column-name">Name</th>
                    <th class="lp-column-type">Type</th>
                    <th class="lp-column-actions">Actions</th>
                </tr>
                </thead>
                <lp-list-quiz-questions></lp-list-quiz-questions>

                <!--                <lp-quiz-choose-items></lp-quiz-choose-items>-->
            </table>
        </div>

        <div class="notify-reload">
            <div class="inner"><?php esc_html_e( 'Something went wrong! Please reload to keep editing list quiz questions.', 'learnpress' ); ?></div>
        </div>

    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-quiz-editor', {
            template: '#tmpl-lp-quiz-editor',
            created: function () {
                setInterval(function () {
                    $store.dispatch('heartbeat');
                }, 60 * 1000);
            },
            computed: {
                heartbeat: function () {
                    return $store.getters['heartbeat'];
                }
            }
        })

    })(Vue, LP_Quiz_Store);
</script>
