<?php
/**
 * Display the title of a section.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$user   = learn_press_get_current_user();
$course = learn_press_get_the_course();
//$section_name = apply_filters( 'learn_press_curriculum_section_name', $section->section_name, $section );
$force = isset( $force ) ? $force : false;

if ( ! isset( $section ) ) {
	return;
}

$title = $section->get_title();

if ( ! $title ) {
	return;
}
?>

<h4 class="section-header">

	<?php echo $title; ?>&nbsp;

	<?php if ( $description = $section->get_description() ) { ?><?php //apply_filters( 'learn_press_curriculum_section_description', $section->section_description, $section ) ) { ?>
        <p><?php echo $description; ?></p>
	<?php } ?>

    <span class="meta">
        <span class="step"><?php printf( __( '%d/%d', 'learnpress' ), $section->get_completed_items( $user->get_id() ), $section->count_items() ); ?></span>
        <span class="collapse"></span>
    </span>

</h4>
