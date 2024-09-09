<?php

/**
 * Course editor template.
 *
 * @since 3.0.0
 */

// $course     = learn_press_get_course();
// $curriculum = $course->get_curriculum_raw();
$all_item = '123';
?>

<div id="admin-editor-lp_course-refactor">
	<div class="lp-course-curriculum">
		<div class="heading">
			<h4> <?php esc_html_e( 'Details', 'learnpress' ); ?><span class="status success"></span></h4>
			<div class="section-item-counts">
				<span>
					<?php echo sprintf( '%s Items', $all_item ); ?>
				</span>
			</div>
			<span class="collapse-sections close"></span>
		</div>
		<div class="curriculum-sections ui-sortable">
			<?php
			// foreach ( $curriculum as $index => $curriculum_item ) {
			// 	$curriculum_id    = $curriculum_item['id'] ?? 0;
			// 	$curriculum_title = $curriculum_item['title'] ?? '';
			// 	$desc             = $curriculum_item['description'] ?? '';
			// 	$count_item       = count( $curriculum_item['items'] ) ?? 0;
			// 	$items            = $curriculum_item['items'] ?? array();
			// 	learn_press_admin_view(
			// 		'course/curriculum/curriculum-item',
			// 		[
			// 			'section_order' => $index,
			// 			'curriculum_id' => $curriculum_id,
			// 			'title'         => $curriculum_title,
			// 			'desc'          => $desc,
			// 			'items'         => $items,
			// 			'count_item'    => $count_item,
			// 		]
			// 	);
			// }
			?>
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