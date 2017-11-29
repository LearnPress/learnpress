<?php
/**
 * Course editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/curriculum' );
learn_press_admin_view( 'course/modal-choose-items' );

?>
<script type="text/x-template" id="tmpl-lp-course-editor">
    <div id="admin-editor-lp_course" :class="[{'need-reload': !heartbeat}, 'lp-admin-editor']">
        <form @submit.prevent="">
            <lp-curriculum></lp-curriculum>
        </form>

        <lp-curriculum-choose-items></lp-curriculum-choose-items>

        <div class="notify-reload">
            <div class="inner"><?php esc_html_e( 'Something went wrong! Please reload to keep editing curriculum.', 'learnpress' ); ?></div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-course-editor', {
            template: '#tmpl-lp-course-editor',
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
        });

    })(Vue, LP_Curriculum_Store);
</script>
