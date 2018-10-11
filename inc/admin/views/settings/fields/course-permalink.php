<?php

$settings = LP()->settings;
global $wp_post_types;
if ( !empty( $wp_post_types[LP_COURSE_CPT] ) ) {
	$course_type          = $wp_post_types[LP_COURSE_CPT];
	$default_courses_slug = $course_type->rewrite['slug'];
} else {
	$default_courses_slug = '';
}

$course_permalink = $settings->get( 'course_base' );
$courses_page_id  = learn_press_get_page_id( 'courses' );
$base_slug        = urldecode( ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learnpress' ) );
$course_base      = _x( 'course', 'default-slug', 'learnpress' );

/*if ( !$course_permalink ) {
	global $wpdb;
	if ( $wpdb->get_results( $wpdb->prepare( "SELECT count(option_id) FROM {$wpdb->options} WHERE option_name = %s", 'learn_press_course_base' ) ) == 0 ) {
		//$course_permalink = '/courses';
	}
}*/
$structures = array(
	0 => array(
		'value' => '',
		'text'  => __( 'Default', 'learnpress' ),
		'code'  => esc_html( home_url() ) . '/?lp_course=sample-course'
	),
	1 => array(
		'value' => '/' . trailingslashit( $course_base ),
		'text'  => __( 'Course', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/sample-course/', home_url(), $course_base ) )
	),
	2 => array(
		'value' => '/' . trailingslashit( $base_slug ),
		'text'  => __( 'Courses base', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/sample-course/', home_url(), $base_slug ) )
	),
	3 => array(
		'value' => '/' . trailingslashit( $base_slug ) . trailingslashit( '%course_category%' ),
		'text'  => __( 'Courses base with category', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/course-category/sample-course/', home_url(), $base_slug ) )
	)
);

$base_type = get_option( 'learn_press_course_base_type' );
$is_custom = ( $base_type == 'custom' && $course_permalink != '' );

?>
<?php foreach ( $structures as $k => $structure ): ?>
	<tr class="learn-press-single-course-permalink<?php if ( $k == 2 || $k == 3 ) {
		echo ' learn-press-courses-page-id';
		echo !$courses_page_id ? ' hide-if-js' : '';
	}; ?>">
		<th>
			<?php
			$is_checked = ( $course_permalink == '' && $structure['value'] == '' ) || ( $structure['value'] == trailingslashit( $course_permalink ) );
			$is_checked = checked( $is_checked, true, false );
			if ( $is_custom && $is_checked ) {
				$is_custom = false;
			}
			?>
			<label>
				<input name="<?php echo $this->get_field_name( "course_base" ); ?>" type="radio" value="<?php echo esc_attr( $structure['value'] ); ?>" class="learn-press-course-base" <?php echo $is_checked; ?> />
				<?php echo $structure['text']; ?>
			</label>
		</th>
		<td>
			<code><?php echo $structure['code']; ?></code>
		</td>
	</tr>
<?php endforeach; ?>
<tr class="learn-press-single-course-permalink custom-base">
	<th>
		<label>
			<input name="<?php echo $this->get_field_name( "course_base" ); ?>" id="learn_press_custom_permalink" type="radio" value="custom" <?php checked( $is_custom, true ); ?> />
			<?php _e( 'Custom Base', 'learnpress' ); ?>
		</label>
	</th>
	<td>
		<input name="course_permalink_structure" id="course_permalink_structure" <?php if ( !$is_custom ) {
			echo 'readonly="readonly"';
		} ?> type="text" value="<?php if($course_permalink) echo esc_attr( trailingslashit( $course_permalink ) ); ?>" class="regular-text code" />
		<p class="description"><?php _e( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default values instead.', 'learnpress' ); ?></p>
	</td>
</tr>
