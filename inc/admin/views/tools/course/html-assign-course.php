<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.2.4
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-assign-course" class="card">
	<h2><?php _e( 'Assign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<p>
			<?php _e( 'User can enroll in a specific course by manually assign to them.', 'learnpress' ); ?>
		</p>
		<p>
			<i>
				<?php _e( 'Noted: when assign user to course, the progress old of user with course assign will eraser, so be careful before do this.', 'learnpress' ); ?>
			</i>
		</p>
	</div>
	<div class="content">
		<form id="lp-assign-user-course-form" name="" method="post">
			<fieldset class="lp-assign-course__options">
				<legend><?php _e( 'Options', 'learnpress' ); ?></legend>
				<ul>
					<li>
						<label>
							Choose Course:
							<select name="course_ids" class="lp-tom-select" style="width: 100%;" multiple>
									<option value=""><?php _e( 'Search courses', 'learnpress' ); ?></option>
							</select>
						</label>
					</li>
					<li><p><b><?php _e( 'Assign To:', 'learnpress' ); ?></b></p>
						<div class="assign-to-container">
							<div class="assign-to-user">
								<input type="radio" name="assign-to" value="user" id="assign-to-user">
								<label for="assign-to-user"><b><?php _e( 'Users', 'learnpress' ); ?></b></label><br>
								<select id="assign-to-user-select" multiple style="width:100%"></select>
							</div>
							<div class="assign-to-role">
								<input type="radio" name="assign-to" value="role" id="assign-to-role">
								<label for="assign-to-role"><b><?php _e( 'Roles', 'learnpress' ); ?></b></label><br>
								<select id="assign-to-role-select" multiple style="width:100%"></select>
							</div>
						</div>
					</li>
					<input type="hidden" id="assign-course-message"
					data-placeholder-course="<?php _e( 'Select a course.', 'learnpress' ); ?>"
					data-placeholder-student="<?php _e( 'Select students.', 'learnpress' ); ?>"
					data-placeholder-role="<?php _e( 'Select roles.', 'learnpress' ); ?>"
					data-select-type="<?php _e( 'Select Assign Type.', 'learnpress' ); ?>"
					data-select-data="<?php _e( 'Select Users Or Roles', 'learnpress' ); ?>"
					data-assign-error="<?php _e( 'Cannot assign users to course', 'learnpress' ); ?>"
					data-unassign-error="<?php _e( 'Cannot remove users.', 'learnpress' ); ?>"
					/>
				</ul>
			</fieldset>
			<p><button class="button button-primary lp-button-assign-course" type="submit"><?php _e( 'Assign', 'learnpress' ); ?></button></p>
		</form>
	</div>
</div>
