<?php
/**
 * Template for edit course curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

use LearnPress\TemplateHooks\Course\AdminEditCurriculum;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $sections_items ) ) {
	$sections_items = [];
}
?>

<div id="admin-editor-lp_course" class="lp-admin-editor">
	<div class="lp-course-curriculum">
		<div class="heading">
			<h4>Details
				<span class="status"></span>
			</h4>
			<div class="section-item-counts"><span>1 Item</span></div>
			<span class="collapse-sections open"></span>
		</div>
		<?php echo AdminEditCurriculum::instance()->html_edit_sections( $sections_items ); ?>
	</div>
</div>
