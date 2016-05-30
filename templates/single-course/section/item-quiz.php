<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $course;
$viewable = learn_press_user_can_view_quiz( $item->ID, $course->id );//learn_press_is_enrolled_course();
$tag      = $viewable ? 'a' : 'span';
$target   = apply_filters( 'learn_press_section_item_link_target', '_blank', $item );
?>

<li <?php learn_press_course_quiz_class( $item->ID ); ?> data-type="<?php echo $item->post_type;?>">

	<?php do_action( 'learn_press_before_section_item_title', $item, $section, $course ); ?>

	<<?php echo $tag; ?> class="course-item-title" target="<?php echo $target; ?>" <?php echo $viewable ? 'href="' . get_the_permalink( $item->ID ) . '"' : ''; ?> data-id="<?php echo $item->ID; ?>">

		<?php echo apply_filters( 'learn_press_section_item_title', get_the_title( $item->ID ), $item ); ?>

	</<?php echo $tag; ?>>

	<?php do_action( 'learn_press_after_section_item_title', $item, $section, $course ); ?>

</li>