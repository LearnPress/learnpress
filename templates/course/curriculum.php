<?php
/**
 * Template for displaying the curriculum of a course
 */
// template variable $curriculum
learn_press_prevent_access_directly();
global $course;
$curriculum_heading = apply_filters( 'learn_press_curriculum_heading', __( 'Course Curriculum', 'learn_press' ) );
?>
    <?php do_action( 'learn_press_before_course_curriculum' );?>
    <div class="course-curriculum">
		<h3><?php echo $curriculum_heading; ?></h3>
        <?php if( $curriculum = $course->get_curriculum() ): ?>
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
