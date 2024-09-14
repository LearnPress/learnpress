<?php

/**
 * Course editor template.
 *
 * @since 3.0.0
 */

use LearnPress\Models\CourseModel;

$course_id = get_the_ID();
$course    = CourseModel::find( $course_id, true );
if ( empty( $course ) ) {
	return;
}
$total_item = $course->get_total_items()->count_items ?? 0;
?>

<div id="admin-editor-lp_course-refactor">
	<div class="lp-course-curriculum">
		<div class="heading">
			<h4> <?php esc_html_e( 'Details', 'learnpress' ); ?><span class="status success"></span></h4>
			<div class="section-item-counts">
				<span>
					<?php echo sprintf( '%s Items', $total_item ); ?>
				</span>
			</div>
			<span class="collapse-sections close"></span>
		</div>
		<div class="curriculum-sections ui-sortable">
			<div class="add-new-section">
				<div class="section new-section">
					<form>
						<div class="section-head">
							<span class="creatable"></span>
							<input type="text" title="title" placeholder="Create a new section" class="title-input">
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<?php learn_press_admin_view( 'course/curriculum/popup-select-item' ); ?>