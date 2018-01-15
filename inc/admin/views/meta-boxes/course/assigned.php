<?php
/**
 * Admin View: Lesson, Quiz assigned Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
global $post;

$courses = learn_press_get_item_courses( $post->ID ); ?>
<div class="lp-item-assigned">
<?php if ( $courses ) { ?>
    <ul>
	<?php foreach ( $courses as $course ) { ?>
        <li>
            <strong><a href="<?php echo get_edit_post_link( $course->ID ); ?> "
               target="_blank"><?php echo get_the_title( $course->ID ); ?></a></strong>

            &#8212;
            <a href="<?php echo learn_press_get_course_permalink( $course->ID ); ?>" target="_blank"><?php _e('View', 'learnpress');?></a>
        </li>
	<?php } ?>
    </ul>
<?php } else {
	_e( 'Not assigned yet', 'learnpress' );
}?>
</div>
