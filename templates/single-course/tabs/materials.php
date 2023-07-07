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
echo do_shortcode( '[learn_press_course_materials]' );

