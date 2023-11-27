<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.2.4
 */

defined( 'ABSPATH' ) or die();
?>
<div id="learn-press-unassigned-course" class="card">
	<h2><?php _e( 'Unassign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<div><?php _e( 'Remove user from a course', 'learnpress' ); ?></div>
		<i style="color: #a20707">
			<?php
			_e(
				'Noted: when remove user from course, the progress of user with course assign will eraser, so be careful before do this.',
				'learnpress'
			);
			?>
		</i>
	</div>
	<div class="content">
		<form id="lp-unassign-user-course-form">
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
			<p>
				<button class="button button-primary lp-button-unassign-course" type="submit">
					<?php _e( 'Remove', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</p>
		</form>
	</div>
</div>
