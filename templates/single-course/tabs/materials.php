<?php
/**
 * Template for displaying downloadable material of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/materials.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.3.0
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
$lp_file = LP_WP_Filesystem::instance();
?>
<?php do_action( 'learn-press/before-single-course-material' ); ?>
<style type="text/css">
    .course-material-table{ width:100%; }
    .course-material-table th{ text-align:left; }
    .course-material-table tfoot td { text-align:left; font-weight:bold; }
</style>
<table class="course-material-table" >
    <thead>
        <tr>
            <th colspan="4"><?php esc_html_e( 'Name', 'learnpress' ) ?></th>
            <th><?php esc_html_e( 'Type', 'learnpress' ) ?></th>
            <th><?php esc_html_e( 'Size', 'learnpress' ) ?></th>
            <th><?php esc_html_e( 'Download', 'learnpress' ) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $materials as $m ): ?>
    <tr>
        <td colspan="4"><?php esc_html_e( $m->file_name ) ?></td>
        <td><?php esc_html_e( strtoupper( wp_check_filetype( basename( $m->file_path ) )['ext'] ) ) ?></td>
        <td><?php 
            if ( $m->method == 'upload' ) {
                $file_size = filesize( wp_upload_dir()['basedir'] . $m->file_path );
                esc_html_e( ( $file_size/1024<1024 ) ? round( $file_size/1024, 2 ).'KB' : round( $file_size/1024/1024, 2 ).'MB' );
            } else {
                esc_html_e( $lp_file->get_file_size_from_url( $m->file_path ));
            }
        ?></td>
        <td><?php esc_html_e( 'Download', 'learnpress' ) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4"><?php esc_html_e( 'Name', 'learnpress' ) ?></td>
            <td><?php esc_html_e( 'Type', 'learnpress' ) ?></td>
            <td><?php esc_html_e( 'Size', 'learnpress' ) ?></td>
            <td><?php esc_html_e( 'Download', 'learnpress' ) ?></td>
        </tr>
    </tfoot>
</table>
<?php do_action( 'learn-press/after-single-course-material' ); ?>