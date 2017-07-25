<?php
/**
 * Template curriculum course.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/sections' );
?>
<script type="text/x-template" id="tmpl-lp-course-curriculum">
    <div id="lp-course-curriculum" class="lp-course-curriculum">
        <div class="heading">
            <h4><?php _e( 'Curriculum', 'learnpress' ); ?></h4>
            <p class="description"><?php _e( 'Outline your course and add content with sections, lessons and quizzes.', 'learnpress' ); ?></p>
        </div>

        <lp-list-sections :sections="sections"></lp-list-sections>
        <input type="hidden" :value="dataSections" name="lp_curriculum_sections">
    </div>
</script>
