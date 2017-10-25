<?php
/**
 * Template for display button for completing action.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die;

$course   = LP_Global::course();
$user     = LP_Global::user();
$item = LP_Global::course_item();

if ( $item->is_preview() || $user->has_completed_item($item->get_id(), $course->get_id()) ) {
	return;
}


$security = $item->create_nonce( 'complete' );

?>
<form method="post" name="learn-press-form-complete-lesson" class="learn-press-form">
    <input type="hidden" name="id" value="<?php echo $item->get_id(); ?>"/>
    <input type="hidden" name="course_id" value="<?php echo $course->get_id(); ?>"/>
    <input type="hidden" name="complete-lesson-nonce" value="<?php echo esc_attr( $security ); ?>"/>
    <input type="hidden" name="type" value="lp_lesson"/>
    <input type="hidden" name="lp-ajax" value="complete-lesson"/>
    <input type="hidden" name="noajax" value="yes"/>
    <button class="button-complete-item button-complete-lesson"
            @click="completeItem"><?php echo __( 'Complete', 'learnpress' ); ?></button>
</form>
