<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$user         = learn_press_get_current_user();
$course       = learn_press_get_the_course();
$section_name = apply_filters( 'learn_press_curriculum_section_name', $section->section_name, $section );
$force        = isset( $force ) ? $force : false;

if ( $section_name === false ) {
	return;
}
?>

<div class="section-header">
	<?php if ( $section_name ) { ?>
        <h4><?php echo $section_name; ?>&nbsp;</h4>
	<?php } ?>
	<?php if ( $section_description = apply_filters( 'learn_press_curriculum_section_description', $section->section_description, $section ) ) { ?>
        <p><?php echo $section_description; ?></p>
	<?php } ?>
    <div class="meta">
        <span class="step"><?php printf( __( '%d/%d', 'learnpress' ), $user->get_completed_items_in_section( $course->id, $section->section_id, $force ), sizeof( $section->items ) ); ?></span>
        <span class="collapse"></span>
    </div>
</div>
