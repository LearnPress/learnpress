<?php
/**
 * Template for displaying lesson item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_lesson.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 *
 * @var LP_Course_Item $itemx
 */
defined( 'ABSPATH' ) || exit();

$item   = LP_Global::course_item();
$course = LP_Global::course();

?>

<div <?php learn_press_content_item_summary_class();?>>

	<?php

	do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );

	?>

</div>

<?php /*
<div :class="mainClass()" data-classes="<?php echo join( ' ', learn_press_content_item_summary_main_classes() ); ?>">
    <!--    <div class="content-item-scrollable">-->
    <!--        <div class="content-item-wrap">-->
    [[{{currentItem.id}}, {{courseLoaded}}]]
	<?php
	foreach ( $course->get_sections() as $section ) {
		foreach ( $section->get_items() as $itemx ) {
			?>
            <div v-show="isShowItem(<?php echo $itemx->get_id(); ?>)">
				<?php echo $itemx->get_content(); ?>
            </div>
			<?php
		}
	}
	?>

    <button type="button" @click="_completeItem($event)" :disabled="currentItem.completed">
        <template v-if="currentItem.completed">{{'<?php esc_html_e( 'Completed', 'learnpress' ); ?>'}}</template>
        <template v-else>{{'<?php esc_html_e( 'Complete', 'learnpress' ); ?>'}}</template>
    </button>
</div>
*/ ?>
