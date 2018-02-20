<script type="text/html" id="tmpl-course-editor">
	<div id="learn-press-course-editor" class="course-editor">
		<div id="course-item-editor"></div>
		<div id="course-curriculum">

		</div>
		<button class="button button-add-section" type="button"><?php esc_html_e( 'Add Section', 'learnpress' ); ?></button>
	</div>
</script>

<script type="text/html" id="tmpl-course-section">
	<div class="course-section" data-section_id="{{data.section_id}}" data-temp_id="{{data.temp_id}}">
		<input type="hidden" name="LP[section][{{data.temp_id}}][_origin_id]" value="{{data.section_id}}" />
		<div class="section-head">
			<input type="text" class="section-name" name="LP[section][{{data.temp_id}}][title]" placeholder="<?php esc_attr_e( 'New section title', 'learnpress' ); ?>" value="{{data.section_name}}" />
			<span class="section-move"></span>
			<div class="course-row-actions">
				<a class="remove" data-action="remove" href=""><?php esc_html_e( 'Remove', 'learnpress' ); ?></a>
				<a class="add" data-action="add" href=""><?php esc_html_e( 'Add', 'learnpress' ); ?></a>
				<a class="toggle" data-action="toggle" href="">+</a>
			</div>
		</div>
		<div class="section-body">
			<ul class="section-items section-empty">

			</ul>
			<div class="section-add-content">
				<ul class="section-content-types">
					<li class="dashicons dashicons-clock" data-post_type="lp_quiz" data-post_title="<?php esc_attr_e( 'New quiz name', 'learnpress' ); ?>"></li>
					<li class="dashicons dashicons-media-default" data-post_type="lp_lesson" data-post_title="<?php esc_attr_e( 'New lesson name', 'learnpress' ); ?>"></li>
				</ul>
				<button class="button button-add-content"><?php esc_html_e( 'Add Content', 'learnpress' ); ?></button>
				<button class="button button-add-exists-content"><?php esc_html_e( 'Add Existing Content', 'learnpress' ); ?></button>
			</div>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-course-section-item">
	<li class="course-section-item" data-ID="{{data.ID}}" data-temp_id="{{data.temp_id}}">
		<input type="hidden" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][_origin_id]" value="{{data.section_item_id}}" />
		<input type="hidden" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][item_id]" value="{{data.ID}}" />
		<input type="hidden" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][item_type]" value="{{data.post_type}}" />

		<input type="text" class="item-name" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][title]" placeholder="<?php esc_attr_e( 'New item title', 'learnpress' ); ?>" value="{{data.post_title}}">
		<span class="section-item-move"></span>
		<div class="course-row-actions ">
			<a class="remove" data-action="remove-item" href=""><?php esc_html_e( 'Remove', 'learnpress' ); ?></a>
			<a class="quick-edit" data-action="quick-edit" href=""><?php esc_html_e( 'Quick edit', 'learnpress' ); ?></a>
		</div>
	</li>
</script>

<script type="text/html" id="tmpl-course-item-editor">
	<div class="course-item-editor">
		<ul>
			<li>
				<h3><?php esc_html_e( 'Title', 'learnpress' ); ?></th></h3>
				<input type="text" class="regular-text" value="{{data.post_title}}" />
			</li>
			<li>
				<h3><?php esc_html_e( 'Content', 'learnpress' ); ?></h3>
				<#
					var editor = jQuery(wp.template('course-item-wp-editor')({id: 'course-item-content-'+data.temp_id, content: data.post_content})).html();
					#>
					{{{editor}}}
			</li>
		</ul>
	</div>
</script>

<script type="text/html" id="tmpl-course-item-wp-editor">
	<div>
		<div id="wp-{{data.id}}-wrap" class="wp-core-ui wp-editor-wrap html-active" style="visibility: hidden">
			<div id="wp-{{data.id}}-editor-tools" class="wp-editor-tools hide-if-no-js">
				<div id="wp-{{data.id}}-media-buttons" class="wp-media-buttons">
					<button type="button" id="insert-media-button" class="button insert-media add_media" data-editor="{{data.id}}">
						<span class="wp-media-buttons-icon"></span> Add Media
					</button>
				</div>
				<div class="wp-editor-tabs">
					<button type="button" id="{{data.id}}-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="{{data.id}}">Visual</button>
					<button type="button" id="{{data.id}}-html" class="wp-switch-editor switch-html" data-wp-editor-id="{{data.id}}">Text</button>
				</div>
			</div>
			<div id="wp-{{data.id}}-editor-container" class="wp-editor-container">
				<div id="qt_{{data.id}}_toolbar" class="quicktags-toolbar"></div>
				<textarea class="wp-editor-area" rows="20" autocomplete="off" cols="40" name="{{data.id}}" id="{{data.id}}">{{data.content}}</textarea>
			</div>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-section-content-types">
	<div class="section-content-types">
		<span class="dashicons dashicons-clock" data-post_type="lp_quiz"></span>
		<span class="dashicons dashicons-media-default" data-post_type="lp_lesson"></span>
	</div>
</script>

<?php

global $post;
$course = learn_press_get_course( $post->ID );
$json = array(
	'sections' => $course->get_curriculum()
)
?>
<script type="text/javascript">
	var Course_Settings = <?php echo wp_json_encode( $json, JSON_PRETTY_PRINT );?>
</script>
<div id="course-editor" class="course-editor">
	<div class="course-curriculum">
		asdsadsad
	</div>
	<div class="course-edit-item">
		asdasdasdsa
	</div>
</div>