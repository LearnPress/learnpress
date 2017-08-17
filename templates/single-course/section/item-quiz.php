<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $item ) ) {
	return;
}
?>
<li>
	<?php
	$user = learn_press_get_current_user();
	printf( '%s (%d)', $item->get_title(), $item->get_id() );
	$status = '';
	//for ( $i = 0; $i < 10000; $i ++ ) {
	$status = $user->get_item_status( $item->get_id(), get_the_ID() );
	//}
	//print_r( $status );
	echo $user->get_course_status( get_the_ID() );
	?>
</li>
<?php
return;


$user        = learn_press_get_current_user();
$course      = LP()->global['course'];
$viewable    = learn_press_user_can_view_quiz( $item->ID, $course->get_id() );//learn_press_is_enrolled_course();
$tag         = $viewable ? 'a' : 'span';
$target      = apply_filters( 'learn_press_section_item_link_target', '_blank', $item );
$item_title  = apply_filters( 'learn_press_section_item_title', get_the_title( $item->ID ), $item );
$item_link   = $viewable ? 'href="' . $course->get_item_link( $item->ID ) . '"' : '';
$item_status = $user->get_item_status( $item->ID );
$result      = $user->get_quiz_results( $item->ID );
$has_result  = false;
if ( in_array( $item_status, array( 'completed', 'started' ) ) ) {
	$has_result = true;
}
$class = '';
if ( $has_result ) {
	$class = 'item-has-result';
}
?>

<li <?php learn_press_course_item_class( $item->ID, $course->get_id(), $class ); ?>
        data-type="<?php echo $item->post_type; ?>">
	<?php do_action( 'learn_press_before_section_item_title', $item, $section, $course ); ?>

	<?php
	printf(
		'<%s class="%s" target="%s" data-id="%d" %s>%s</%s>',
		$tag,
		'course-item-title button-load-item',
		$target,
		$item->ID,
		$item_link,
		$item_title,
		$tag
	);
	?>
    <!--<<?php echo $tag; ?> class="course-item-title button-load-item" target="<?php echo $target; ?>" <?php echo $item_link; ?> data-id="<?php echo $item->ID; ?>"><?php echo $item_title ?></<?php echo $tag; ?>>-->
	<?php do_action( 'learn_press_after_section_item_title', $item, $section, $course ); ?>
</li>