<?php
/**
 * Template for displaying item section meta in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/item-meta.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<div class="course-item-meta">

	<?php do_action( 'learn-press/course-section-item/before-' . $item->get_item_type() . '-meta', $item ); ?>
	<?php if ( $item->is_preview() ) { ?>
		<?php $course_id = $section->get_course_id(); ?>
		<?php if ( get_post_meta( $course_id, '_lp_required_enroll', true ) == 'yes' ) { ?>
            <i class="item-meta course-item-status"
               data-preview="<?php esc_html_e( 'Preview', 'learnpress' ); ?>"></i>
		<?php } ?>
	<?php } else { ?>
        <i class="fa item-meta course-item-status trans"></i>
	<?php } ?>

	<?php do_action( 'learn-press/course-section-item/after-' . $item->get_item_type() . '-meta', $item ); ?>
</div>
