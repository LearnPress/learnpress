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
$lp_file = LP_WP_Filesystem::instance();
?>
<?php do_action( 'learn-press/before-lesson-materials' ); ?>
<style type="text/css">
    .course-material-table{ width:100%; }
    .course-material-table th:first-child{ text-align:left; }
    .course-material-table tr td:not(:first-child){ text-align:center; }
    .course-material-table tfoot td { text-align:left; font-weight:bold; }
</style>
<h5 class="course-item-title" style="margin-top:24px;"><?php esc_html_e( 'Downloadable materials', 'learnpress' ) ?></h5>
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
        <td>
        	<a href="#">
        		<i class="fas fa-file-download btn-download-material" file="<?php echo esc_attr( $m->file_id ) ?>"></i>
            </a>
        </td>
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
<?php do_action( 'learn-press/after-lesson-materials' ); ?>