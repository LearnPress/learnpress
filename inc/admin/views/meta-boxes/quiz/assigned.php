<?php
/**
 * Admin View: Question assigned Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
global $post;

// question curd
$curd = new LP_Question_CURD();
// get quiz
$quiz = $curd->get_quiz( $post->ID );
?>
<div class="lp-item-assigned">
<?php if ( $quiz ) { ?>
	<?php if ( $courses = learn_press_get_item_courses( $quiz->ID ) ) { ?>
        <ul class="parent-courses">
			<?php foreach ( $courses as $course ) { ?>
                <li>
                    <strong>
                        <a href="<?php echo get_edit_post_link( $course->ID ); ?>"
                       target="_blank"><?php echo get_the_title( $course->ID ); ?></a>
                    </strong>
                    &#8212;
                    <a href="<?php echo learn_press_get_course_permalink( $course->ID ); ?>" target="_blank"><?php _e('View', 'learnpress');?></a>
                    <ul class="parent-quizzes">
                        <li>
                            <strong>
                                <a href="<?php echo get_edit_post_link( $quiz->ID ); ?> "
                                       target="_blank">
                                    &#8212; &#8212;
                                    <?php echo get_the_title( $quiz->ID ); ?></a>
                            </strong>
                            &#8212;
                            <a href="<?php echo learn_press_get_course_item_permalink( $course->ID, $quiz->ID ); ?>" target="_blank"><?php _e('View', 'learnpress');?></a>
                        </li>
                    </ul>
                </li>
			<?php } ?>
        </ul>
	<?php } else { ?>
        <strong><a href="<?php echo get_edit_post_link( $quiz->ID ); ?> "
           target="_blank"><?php echo get_the_title( $quiz->ID ); ?></a></strong>
	<?php } ?>
<?php } else {
	_e( 'Not assigned yet', 'learnpress' );
}?>
</div>