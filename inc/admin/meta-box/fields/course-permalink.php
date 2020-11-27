<?php
flush_rewrite_rules();

$settings = LP()->settings;
global $wp_post_types;

if ( ! empty( $wp_post_types[ LP_COURSE_CPT ] ) ) {
	$course_type          = $wp_post_types[ LP_COURSE_CPT ];
	$default_courses_slug = $course_type->rewrite['slug'];
} else {
	$default_courses_slug = '';
}

$course_permalink = $settings->get( 'course_base' );
$courses_page_id  = learn_press_get_page_id( 'courses' );
$base_slug        = urldecode( ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learnpress' ) );
$course_base      = _x( 'course', 'default-slug', 'learnpress' );

$structures = array(
	0 => array(
		'value' => '',
		'text'  => esc_html__( 'Default', 'learnpress' ),
		'code'  => home_url() . '/?lp_course=sample-course',
	),
	1 => array(
		'value' => '/' . trailingslashit( $course_base ),
		'text'  => esc_html__( 'Course', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/sample-course/', home_url(), $course_base ) ),
	),
	2 => array(
		'value' => '/' . trailingslashit( $base_slug ),
		'text'  => esc_html__( 'Courses base', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/sample-course/', home_url(), $base_slug ) ),
	),
	3 => array(
		'value' => '/' . trailingslashit( $base_slug ) . trailingslashit( '%course_category%' ),
		'text'  => esc_html__( 'Courses base with category', 'learnpress' ),
		'code'  => esc_html( sprintf( '%s/%s/course-category/sample-course/', home_url(), $base_slug ) ),
	),
);

$base_type = get_option( 'learn_press_course_base_type' );
$is_custom = ( $base_type == 'custom' && $course_permalink != '' );
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['title']; ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<ul>
			<?php foreach ( $structures as $k => $structure ) : ?>
				<li class="learn-press-single-course-permalink
				<?php
				if ( $k == 2 || $k == 3 ) {
					echo ' learn-press-courses-page-id';
					echo ! $courses_page_id ? ' hide-if-js' : '';
				}
				?>
				" style="margin-bottom: 20px;">
					<?php
					$is_checked = ( $course_permalink == '' && $structure['value'] == '' ) || ( $structure['value'] == trailingslashit( $course_permalink ) );
					$is_checked = checked( $is_checked, true, false );

					if ( $is_custom && $is_checked ) {
						$is_custom = false;
					}
					?>
					<label>
						<input name="<?php echo $value['id']; ?>" type="radio" value="<?php echo esc_attr( $structure['value'] ); ?>" class="learn-press-course-base" <?php echo $is_checked; ?> />
						<?php echo $structure['text']; ?>
						<p><code><?php echo $structure['code']; ?></code></p>
					</label>
				</li>
			<?php endforeach; ?>

			<li class="learn-press-single-course-permalink custom-base">
				<label>
					<input name="<?php echo $value['id']; ?>" id="learn_press_custom_permalink" type="radio" value="custom" <?php checked( $is_custom, true ); ?> />
					<?php esc_html_e( 'Custom Base', 'learnpress' ); ?>
					<input name="course_permalink_structure" id="course_permalink_structure" readonly="<?php echo ! $is_custom ? 'readonly' : false; ?>" type="text" value="<?php echo $course_permalink ? esc_attr( trailingslashit( $course_permalink ) ) : ''; ?>" class="regular-text code"/>
				</label>
				<p class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default values instead.', 'learnpress' ); ?></p>
			</li>
		</ul>
	</td>
</tr>
