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

/**
 * @var LP_Course_Item    $item
 * @var LP_Course_Section $section
 */
?>

<div class="course-item-meta">

	<?php
	/**
	 * LP Hook
	 */
	do_action( 'learn-press/course-section-item/before-' . $item->get_item_type() . '-meta', $item );
	?>
	<?php
	if ( $item->is_preview() ) {
		?>
        <i class="item-meta course-item-status"
           data-preview="<?php esc_html_e( 'Preview', 'learnpress' ); ?>"></i>
		<?php
	} else {
		?>
        <i class="fa item-meta course-item-status trans"></i>
		<?php
	}
	?>

	<?php do_action( 'learn-press/course-section-item/after-' . $item->get_item_type() . '-meta', $item ); ?>

</div>
