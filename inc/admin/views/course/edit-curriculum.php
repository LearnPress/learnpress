<?php
/**
 * Template for edit course curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="admin-editor-lp_course" class="lp-admin-editor">
	<div class="lp-course-curriculum">
		<div class="heading">
			<h4>Details
				<span class="status successful"></span>
			</h4>
			<div class="section-item-counts"><span>1 Item</span></div>
			<span class="collapse-sections open"></span>
		</div>
		<div class="curriculum-sections ui-sortable">
			<div data-section-order="0" data-section-id="12" class="section open">
				<div class="section-head"><span class="movable lp-sortable-handle ui-sortable-handle"></span>
					<input name="section-title-input" class="title-input" type="text" title="Update section title" placeholder="Create a new section">
					<div class="section-item-counts"><span>1 Item</span></div>
					<div class="actions"><span class="collapse close"></span></div>
				</div>
				<div class="section-collapse" style="display: block;">
					<div class="section-content">
						<div class="details">
							<input type="text"
								title="description"
								placeholder="Section description..."
								class="description-input no-submit">
						</div>
						<div class="section-list-items">
							<ul class="ui-sortable">
								<li data-item-id="5101" data-item-order="1" class="section-item lp_lesson">
									<div class="drag lp-sortable-handle">
										<svg viewBox="0 0 32 32" class="svg-icon">
											<path
												d="M 14 5.5 a 3 3 0 1 1 -3 -3 A 3 3 0 0 1 14 5.5 Z m 7 3 a 3 3 0 1 0 -3 -3 A 3 3 0 0 0 21 8.5 Z m -10 4 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 12.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 12.5 Z m -10 10 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 22.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 22.5 Z"></path>
										</svg>
									</div>
									<div class="icon"></div>
									<div class="title"><input type="text"></div>
									<div class="item-actions">
										<div class="actions">
											<div data-content-tip="Enable/Disable Preview"
												class="action preview-item lp-title-attr-tip ready"
												data-id="682c106ede4c0"><a
													class="lp-btn-icon dashicons dashicons-hidden"></a></div>
											<div data-content-tip="Edit an item"
												class="action edit-item lp-title-attr-tip ready"
												data-id="682c106ede4c1"><a
													href="https://testaddon.thimpress.com/wp-admin/post.php?action=edit&amp;post=5101"
													target="_blank"
													class="lp-btn-icon dashicons dashicons-edit"></a></div>
											<div class="action delete-item"><a
													class="lp-btn-icon dashicons dashicons-trash"></a>
												<ul class="ui-sortable" style="">
													<li><a>Remove from the course</a></li>
													<li><a class="delete-permanently">Move to trash</a></li>
												</ul>
											</div>
										</div>
									</div>
								</li>
							</ul>
							<div class="new-section-item section-item">
								<div class="drag lp-sortable-handle"></div>
								<div class="types"><label title="Lesson" class="type lp_lesson current"><input
											type="radio" name="lp-section-item-type"
											value="lp_lesson"></label><label title="Quiz"
																			class="type lp_quiz"><input
											type="radio" name="lp-section-item-type" value="lp_quiz"></label>
								</div>
								<div class="title"><input type="text" placeholder="Create a new lesson"></div>
							</div>
						</div>
					</div>
					<div class="section-actions">
						<button type="button" class="button button-secondary">Select items</button>
						<div class="remove"><span class="icon">Delete</span>
							<div class="confirm">Are you sure?</div>
						</div>
					</div>
				</div>
			</div>
			<div class="add-new-section">
				<div class="section new-section">
					<div class="section-head">
						<span class="creatable"></span>
						<input name="new_section"
							type="text"
							title="Enter title section"
							placeholder="Create a new section"
							class="title-input new-section">
						<button type="button" class="lp-btn-add-new-section">Add Sections</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
