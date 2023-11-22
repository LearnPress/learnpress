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
				<ul>
					<li>
						<label>
							<?php _e( 'Choose Course: (Max 5 courses)', 'learnpress' ); ?>
							<select name="course_ids" class="lp-tom-select" style="width: 100%;" multiple>
								<option value=""><?php _e( 'Search courses', 'learnpress' ); ?></option>
							</select>
						</label>
					</li>
					<li>
						<div class="assign-to-user">
							<label>
								<?php _e( 'Choose User: (Max 5 users)', 'learnpress' ); ?>
								<select name="user-assign" multiple style="width:100%">
									<option value=""><?php _e( 'Search users', 'learnpress' ); ?></option>
								</select>
							</label>
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
			<p>
				<button class="button button-primary lp-button-assign-course"
						type="submit"><?php _e( 'Assign', 'learnpress' ); ?></button>
			</p>
		</form>
	</div>
</div>
