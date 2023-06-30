<?php
/**
 * Template for displaying material files of lesson
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/materials.php.
 *
 * @author   khanhbd@phýcode.com
 * @package  Learnpress/Templates
 * @version  4.2.2
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $item ) || ! isset( $user ) || ! isset( $course ) ) {
	return;
}

if ( $item->is_preview() && ! $user->has_enrolled_course( $course->get_id() ) ) {
	return;
}
if ( ! $materials ) {
	return;
}
$per_page = (int) LP_Settings::get_option( 'material_file_per_page', -1 );
if ( $per_page = 0 ) {
	return;
}
$lp_file = LP_WP_Filesystem::instance();
?>
<div class="lp-material-skeleton">
	<?php do_action( 'learn-press/before-lesson-materials' ); ?>
	<h5 class="course-item-title" style="margin-top:24px;"><?php esc_html_e( 'Downloadable materials', 'learnpress' ); ?></h5>
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
	<?php do_action( 'learn-press/after-lesson-materials' ); ?>
</div>
