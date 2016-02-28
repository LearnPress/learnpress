<?php
/**
 * Display settings for course
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$settings = LP_Settings::instance();
?>
<h3 class=""><?php echo $this->section['title']; ?></h3>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<?php foreach( $this->get_settings() as $field ){?>
		<?php $this->output_field( $field );?>
	<?php }?>
	<?php if( 1 == 0 ){?>
	<tr>
		<th scope="row"><label><?php _e( 'Courses Page', 'learnpress' ); ?></label></th>
		<td>
			<?php
			learn_press_pages_dropdown( $this->get_field_name( "courses_page_id" ), $courses_page_id );
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Course category base', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="<?php echo $this->get_field_name( "course_category_base" ); ?>" value="<?php echo $settings->get( 'course_category_base' ); ?>" placeholder="<?php echo 'course-category'; ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Course tag base', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="<?php echo $this->get_field_name( "course_tag_base" ); ?>" value="<?php echo $settings->get( 'course_tag_base' ); ?>" placeholder="<?php echo 'course-tag'; ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Review course before publish', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( "required_review" ); ?>" value="no" />
			<input type="checkbox" name="<?php echo $this->get_field_name( "required_review" ); ?>" value="yes" <?php checked( $settings->get( 'required_review' ) == 'yes' );?> />
			<p class="description">
				<?php _e( 'The course need to review by admin before it can be published', 'learnpress' );?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Enable edit published course', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( "enable_edit_published" ); ?>" value="no" />
			<input type="checkbox" name="<?php echo $this->get_field_name( "enable_edit_published" ); ?>" value="yes" <?php checked( $settings->get( 'enable_edit_published' ) == 'yes' );?> />
			<p class="description">
				<?php _e( 'Allows instructor edit the course that published without review.<br /> If this option is disabled, the course status will be changed to Pending Review when the instructor update course', 'learnpress' );?>
			</p>
		</td>
	</tr>
	<!-- thumbnail -->
	<tr>
		<th colspan="2">
			<h3><?php _e( 'Course images', 'learnpress' );?></h3>
		</th>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Single course', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" size="4" name="<?php echo $this->get_field_name( "single_course_image_size[width]" ); ?>" value="<?php echo $settings->get( 'single_course_image_size.width' ); ?>" placeholder="" />
			&times;
			<input type="text" size="4" name="<?php echo $this->get_field_name( "single_course_image_size[height]" ); ?>" value="<?php echo $settings->get( 'single_course_image_size.height' ); ?>" placeholder="" />
			<?php _e( 'px', 'learnpress' );?>
			&nbsp;&nbsp;&nbsp;
			<input type="hidden" name="<?php echo $this->get_field_name( "single_course_image_size[crop]" ); ?>" value="no" />
			<label>
				<input type="checkbox" name="<?php echo $this->get_field_name( "single_course_image_size[crop]" ); ?>" value="yes" <?php checked( $settings->get( 'single_course_image_size.crop' ) == 'yes' ); ?>" />
				<?php _e( 'Crop?', 'learn_pres' );?>
			</label>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Course thumbnail', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" size="4" name="<?php echo $this->get_field_name( "course_thumbnail_image_size[width]" ); ?>" value="<?php echo $settings->get( 'course_thumbnail_image_size.width' ); ?>" placeholder="" />
			&times;
			<input type="text" size="4" name="<?php echo $this->get_field_name( "course_thumbnail_image_size[height]" ); ?>" value="<?php echo $settings->get( 'course_thumbnail_image_size.height' ); ?>" placeholder="" />
			<?php _e( 'px', 'learnpress' );?>
			&nbsp;&nbsp;&nbsp;
			<input type="hidden" name="<?php echo $this->get_field_name( "course_thumbnail_image_size[crop]" ); ?>" value="no" />
			<label>
				<input type="checkbox" name="<?php echo $this->get_field_name( "course_thumbnail_image_size[crop]" ); ?>" value="yes" <?php checked( $settings->get( 'course_thumbnail_image_size.crop' ) == 'yes' ); ?>" />
				<?php _e( 'Crop?', 'learn_pres' );?>
			</label>
		</td>
	</tr>
	<!-- permalink -->
	<tr>
		<th colspan="2">
			<h3><?php _e( 'Single course permalink', 'learnpress' );?></h3>
		</th>
	</tr>
	<?php foreach( $structures as $k => $structure ): ?>
	<tr<?php if( $k == 2 || $k == 3 ){ echo ' class="learn-press-courses-page-id'; echo !$courses_page_id ? ' hide-if-js"' : '""'; };?> >
		<th>
			<?php
			$is_checked = checked( ( $course_permalink == '' && $structure['value'] == '' ) || ( $structure['value'] == trailingslashit( $course_permalink ) ), true, false );
			if( $is_custom && $is_checked ) {
				$is_custom = false;
			}
			?>
			<label>
				<input name="<?php echo $this->get_field_name( "course_base" ); ?>" type="radio" value="<?php echo esc_attr( $structure['value'] ); ?>" class="learn-press-course-base" <?php echo $is_checked; ?> />
				<?php echo $structure['text']; ?>
			</label>
		</th>
		<td>
			<code><?php echo $structure['code'];?></code>
		</td>
	</tr>
	<?php endforeach; ?>
	<tr>
		<th>
			<label>
				<input name="<?php echo $this->get_field_name( "course_base" ); ?>" id="learn_press_custom_permalink" type="radio" value="custom" <?php checked( $is_custom, true ); ?> />
				<?php _e( 'Custom Base', 'learnpress' ); ?>
			</label>
		</th>
		<td>
			<input name="course_permalink_structure" id="course_permalink_structure" type="text" value="<?php echo esc_attr( $course_permalink ); ?>" class="regular-text code" />
			<p class="description"><?php _e( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default instead.', 'learnpress' ); ?></p>
		</td>
	</tr>
	<?php }?>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>
<script type="text/javascript">
	jQuery( function($) {
		$('input.learn-press-course-base').change(function() {
			$('#course_permalink_structure').val( $( this ).val() );
		});

		$('#course_permalink_structure').focus( function(){
			$('#learn_press_custom_permalink').click();
		} );

		$('#learn_press_courses_page_id').change(function(){
			$('tr.learn-press-courses-page-id').toggleClass( 'hide-if-js', !parseInt( this.value ) )
		});
	} );
</script>