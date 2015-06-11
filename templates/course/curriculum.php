<?php
/**
 * Template for displaying the curriculum of a course
 */

// template variable $curriculum
learn_press_prevent_access_directly();
do_action( 'learn_press_before_course_curriculum' );
?>
	<div class="course-curriculum" course-id="<?php //echo $course_id ?>">
		<h3><?php _e( 'Course Curriculum', 'learn_press' ) ?></h3>
        <?php if( $curriculum ): ?>
        <ul class="curriculum-sections">
        <?php foreach ( $curriculum as $course_part ) :?>
            <?php learn_press_get_template( 'course/loop-curriculum.php', array( 'curriculum_course' => $course_part ) );?>
        <?php endforeach;?>
        </ul>
        <?php else:?>
        <?php _e( 'Curriculum is empty', 'learn_press' );?>
        <?php endif;?>
	</div>
<?php
do_action( 'learn_press_after_course_curriculum' );
