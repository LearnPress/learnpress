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

if ( ! isset( $section ) ) {
	return;
}

$title = $section->get_title();

?>

<div class="section-header">

    <div class="section-left">
		<?php if ( $title ) { ?>
            <h5 class="section-title"><?php echo $title; ?></h5>
		<?php } ?>

		<?php if ( $description = $section->get_description() ) { ?>
            <p class="section-desc"><?php echo $description; ?></p>
		<?php } ?>
    </div>

    <div class="section-meta">
        <div class="section-progress">
            <div class="progress-bg">
                <div class="progress-active">

                </div>
            </div>
        </div>
        <span class="step"><?php printf( __( '%d/%d', 'learnpress' ), $section->get_completed_items( $user->get_id() ), $section->count_items() ); ?></span>
        <span class="collapse"></span>
    </div>

</div>
