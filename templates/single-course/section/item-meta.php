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

if ( ! isset( $item ) && ! isset( $section ) ) {
	return;
}
?>

<div class="course-item-meta">

	<?php
	do_action( 'learn-press/course-section-item/before-' . $item->get_item_type() . '-meta', $item );

	if ( $item->is_preview() ) {
		$course_id = $section->get_course_id();
		if ( get_post_meta( $course_id, '_lp_required_enroll', true ) == 'yes' ) {
			echo '<i class="item-meta course-item-status"
			   data-preview="' . esc_html__( 'Preview', 'learnpress' ) . '"></i>';
		}
	} else {
		echo '<i class="fa item-meta course-item-status trans"></i>';
	}

	do_action( 'learn-press/course-section-item/after-' . $item->get_item_type() . '-meta', $item );
	?>
</div>
