<?php
/**
 * Course editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/curriculum-v2' );

?>
<script type="text/x-template" id="tmpl-lp-course-editor">
    <div id="course-editor-v2">
        <form>
            <lp-curriculum></lp-curriculum>
        </form>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-course-editor', {
            template: '#tmpl-lp-course-editor'
        });

    })(Vue, LP_Curriculum_Store);
</script>
