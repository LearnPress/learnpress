<?php
/**
 * Template for displaying setup form of static pages while setting up LP
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

$settings = LP_Settings::instance();
?>
<h2><?php _e( 'Static Pages', 'learnpress' ); ?></h2>

<p><?php _e( 'The pages will display the content of LP\'s necessary pages, such as Courses, Checkout, and Profile', 'learnpress' ); ?></p>
<p><?php printf( __( 'If you are not sure, click <a href="%s" id="create-pages">here</a> to create pages automatically.', 'learnpress' ), wp_nonce_url( admin_url( 'index.php?page=lp-setup&step=pages&auto-create' ) ), 'setup-create-pages' ); ?></p>

<table class="form-field">
	<tr>
		<th>
			<?php _e( 'Page: Show a list of courses', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][courses_page_id]', $settings->get( 'courses_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: Show a list of instructors', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][instructors_page_id]', $settings->get( 'instructors_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: single instructor', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][single_instructor_page_id]', $settings->get( 'single_instructor_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: Profile', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][profile_page_id]', $settings->get( 'profile_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: Checkout', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][checkout_page_id]', $settings->get( 'checkout_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: Become a Teacher', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][become_a_teacher_page_id]', $settings->get( 'become_a_teacher_page_id' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Page: Terms and Conditions', 'learnpress' ); ?>
		</th>
		<td>
			<?php learn_press_pages_dropdown( 'settings[pages][term_conditions_page_id]', $settings->get( 'term_conditions_page_id' ) ); ?>
		</td>
	</tr>
</table>
