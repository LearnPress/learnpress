<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @since 4.2.5.6
 * @version 1.0.0
 */
?>
<div id="learn-press-assign-course" class="card">
	<h2><?php _e( 'Assign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<div><?php _e( 'User can enroll in a specific course by manually assign to them.', 'learnpress' ); ?></div>
		<i style="color: #a20707">
			<?php _e( 'Noted: when assign user to course, the progress old of user with course assign will eraser, so be careful before do this.', 'learnpress' ); ?>
		</i>
	</div>
	<div class="content">
		<form id="lp-assign-user-course-form" name="" method="post">
			<fieldset class="lp-assign-course__options">
				<ul>
					<li>
						<label>
							<?php _e( 'Choose Course:', 'learnpress' ); ?>
							<select name="course_ids" class="lp-tom-select" style="width: 100%;" multiple>
								<option value=""><?php _e( 'Search courses', 'learnpress' ); ?></option>
							</select>
						</label>
					</li>
					<li>
						<div class="assign-to-user">
							<label>
								<?php _e( 'Choose User:', 'learnpress' ); ?>
								<select name="user_ids" multiple style="width:100%">
									<option value=""><?php _e( 'Search users', 'learnpress' ); ?></option>
								</select>
							</label>
						</div>
					</li>
				</ul>
			</fieldset>
			<p>
				<button class="button button-primary lp-button-assign-course" type="submit">
					<?php _e( 'Assign', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</p>
		</form>
	</div>
</div>
