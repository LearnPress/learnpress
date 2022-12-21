<?php
/**
 * Template for displaying curriculum tab of single course.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.1.5
 */

defined( 'ABSPATH' ) || exit();

// PARAM: section_item, course_item, can_view_item, user, course_id is required.

/**
 * @var LP_Model_User_Can_View_Course_Item $can_view_item
 * @var LP_Course_Item $course_item
 */
if ( empty( $section_item ) || empty( $course_item ) || empty( $can_view_item ) || empty( $course_id ) ) {
	return;
}
?>

<li class="course-item <?php echo esc_attr( implode( ' ', $course_item->get_class_v2( $course_id, $section_item['ID'], $can_view_item ) ) ); ?>"
	data-id="<?php echo esc_attr( $section_item['ID'] ); ?>">
	<a class="section-item-link" href="<?php echo esc_url_raw( $course_item->get_permalink() ); ?>">
		<span class="item-name"><?php echo esc_html( $section_item['post_title'] ); ?></span>

		<div class="course-item-meta">
			<?php do_action( 'learn-press/course-section-item/before-' . $course_item->get_item_type() . '-meta', $course_item ); ?>

			<?php if ( $course_item->is_preview() ) : ?>
				<span class="item-meta course-item-preview" data-preview="<?php esc_attr_e( 'Preview', 'learnpress' ); ?>"></span>
			<?php endif; ?>

			<span class="item-meta course-item-status" title="<?php echo esc_attr( $course_item->get_status_title() ); ?>"></span>

			<?php do_action( 'learn-press/course-section-item/after-' . $course_item->get_item_type() . '-meta', $course_item ); ?>
		</div>
	</a>
</li>
