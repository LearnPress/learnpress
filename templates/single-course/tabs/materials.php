<?php
/**
 * Template for displaying downloadable material of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/materials.php.
 *
 * @author   khanhbd@physcode.com
 * @package  Learnpress/Templates
 * @version  4.2.2
 */

defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}
/**
 * @var LP_User
 */
$materials = $course->get_downloadable_material();
if ( ! $materials ) {
	return;
}
$per_page = (int) LP_Settings::get_option( 'material_file_per_page', -1 );
if ( $per_page == 0 ) {
	return;
}
$lp_file = LP_WP_Filesystem::instance();
?>

<div class="lp-material-skeleton">
	<?php do_action( 'learn-press/before-single-course-material' ); ?>
	<?php lp_skeleton_animation_html( 5, 100 ); ?>
	<table class="course-material-table" >
		<thead>
			<tr>
				<th colspan="4"><?php esc_html_e( 'Name', 'learnpress' ); ?></th>
				<th><?php esc_html_e( 'Type', 'learnpress' ); ?></th>
				<th><?php esc_html_e( 'Size', 'learnpress' ); ?></th>
				<th><?php esc_html_e( 'Download', 'learnpress' ); ?></th>
			</tr>
		</thead>
		<tbody id="material-file-list">
		</tbody>
	</table>
	<button class="lp-button lp-loadmore-material" page="1"><?php esc_html_e( 'Load more.', 'learnpress' ); ?></button>
	<?php do_action( 'learn-press/after-single-course-material' ); ?>
</div>
