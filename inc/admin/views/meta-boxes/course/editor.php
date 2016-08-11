<script type="text/html" id="tmpl-course-editor">
	<div id="learn-press-course-editor" class="course-editor">
		<div id="course-item-editor"></div>
		<div id="course-curriculum">

		</div>
	</div>
</script>

<script type="text/html" id="tmpl-course-section">
	<div class="course-section" data-section_id="{{data.section_id}}" data-temp_id="{{data.temp_id}}">
		<input type="hidden" name="LP[section][{{data.temp_id}}][_origin_id]" value="{{data.section_id}}" />
		<div class="section-head">
			<input type="text" class="section-name" name="LP[section][{{data.temp_id}}][title]" placeholder="<?php esc_attr_e( 'New section title', 'learnpress' ); ?>" value="{{data.section_name}}"/>
			<span class="section-move"></span>
		</div>
		<ul class="section-items">

		</ul>
	</div>
</script>

<script type="text/html" id="tmpl-course-section-item">
	<li class="course-section-item" data-ID="{{data.ID}}" data-temp_id="{{data.temp_id}}">
		<input type="hidden" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][_origin_id]" value="{{data.section_item_id}}" />
		<input type="hidden" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][item_id]" value="{{data.ID}}" />
		<input type="text" class="item-name" name="LP[section][{{data.section.temp_id}}][items][{{data.temp_id}}][title]" placeholder="<?php esc_attr_e( 'New item title', 'learnpress' ); ?>" value="{{data.post_title}}">
		<span class="section-item-move"></span>
	</li>
</script>

<script type="text/html" id="tmpl-course-item-editor">
	<div class="course-item-editor">
		<ul>
			<li>
				<h3><?php esc_html_e('Title', 'learnpress');?></th></h3>
				<input type="text" class="regular-text" value="{{data.post_title}}" />
			</li>
			<li>
				<h3><?php esc_html_e('Content', 'learnpress');?></h3>
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
		<div id="wp-{{data.id}}-editor-tools" class="wp-editor-tools hide-if-no-js"><div id="wp-{{data.id}}-media-buttons" class="wp-media-buttons"><button type="button" id="insert-media-button" class="button insert-media add_media" data-editor="{{data.id}}"><span class="wp-media-buttons-icon"></span> Add Media</button></div>
			<div class="wp-editor-tabs"><button type="button" id="{{data.id}}-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="{{data.id}}">Visual</button>
				<button type="button" id="{{data.id}}-html" class="wp-switch-editor switch-html" data-wp-editor-id="{{data.id}}">Text</button>
			</div>
		</div>
		<div id="wp-{{data.id}}-editor-container" class="wp-editor-container"><div id="qt_{{data.id}}_toolbar" class="quicktags-toolbar"></div><textarea class="wp-editor-area" rows="20" autocomplete="off" cols="40" name="{{data.id}}" id="{{data.id}}">{{data.content}}</textarea></div>
	</div>
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