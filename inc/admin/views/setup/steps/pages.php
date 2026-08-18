<?php
/**
 * Template for displaying setup form of static pages while setting up LP
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.1
 */

defined( 'ABSPATH' ) or exit;

?>
<h2><?php _e( 'Static Pages', 'learnpress' ); ?></h2>

<p><?php _e( 'The pages will display the content of LP\'s necessary pages, such as Courses, Checkout, and Profile.', 'learnpress' ); ?></p>

<table class="form-field">
	<tr>
		<th>
			<?php _e( 'All courses page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_courses_page_id', learn_press_get_page_id( 'courses' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'All instructors page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_instructors_page_id', learn_press_get_page_id( 'instructors' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Single instructor page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_single_instructor_page_id', learn_press_get_page_id( 'single_instructor' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Profile page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_profile_page_id', learn_press_get_page_id( 'profile' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Checkout page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_checkout_page_id', learn_press_get_page_id( 'checkout' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Become an instructors page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_become_a_teacher_page_id', learn_press_get_page_id( 'become_a_teacher' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Terms and conditions page', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_term_conditions_page_id', learn_press_get_page_id( 'term_conditions' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Logout Redirect', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'learn_press_logout_redirect_page_id', learn_press_get_page_id( 'logout_redirect' ) ); ?>
		</td>
	</tr>
</table>
