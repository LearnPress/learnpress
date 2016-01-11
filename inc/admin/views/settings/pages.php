<?php
/**
 * Display settings for pages
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = LP()->settings;
?>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<!--
	<tr>
		<th scope="row"><label><?php _e( 'Courses Page', 'learn_press' ); ?></label></th>
		<td>
			<?php
			$courses_page_id = $settings->get( 'courses_page_id', 0 );
			learn_press_pages_dropdown( $this->get_field_name( "courses_page_id" ), $courses_page_id );
			?>
		</td>
	</tr>
	-->
	<tr>
		<th scope="row"><label><?php _e( 'Take Course Confirm', 'learn_press' ); ?></label></th>
		<td>
			<?php
			$taken_course_confirm_page_id = $settings->get( 'taken_course_confirm_page_id', 0 );
			learn_press_pages_dropdown( $this->get_field_name( "taken_course_confirm_page_id" ), $taken_course_confirm_page_id );
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Become a Teacher', 'learn_press' ); ?></label></th>
		<td>
			<?php
			$become_teacher_form_page_id = $settings->get( 'become_teacher_form_page_id', 0 );
			learn_press_pages_dropdown( $this->get_field_name( "become_teacher_form_page_id" ), $become_teacher_form_page_id );
			?>
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>