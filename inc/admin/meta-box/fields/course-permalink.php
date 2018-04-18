<?php

/**
 * Class RWMB_Course_Permalink_Field
 *
 * @extend RWMB_Field
 */
class RWMB_Course_Permalink_Field extends RWMB_Field {

	/**
     * Show field.
     *
	 * @param mixed $meta
	 * @param array $field
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		$meta = self::sanitize_meta( $meta );
		// default value

		flush_rewrite_rules();
		ob_start();

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
        <ul>
			<?php foreach ( $structures as $k => $structure ): ?>
                <li class="learn-press-single-course-permalink<?php if ( $k == 2 || $k == 3 ) {
					echo ' learn-press-courses-page-id';
					echo ! $courses_page_id ? ' hide-if-js' : '';
				}; ?>">
					<?php
					$is_checked = ( $course_permalink == '' && $structure['value'] == '' ) || ( $structure['value'] == trailingslashit( $course_permalink ) );
					$is_checked = checked( $is_checked, true, false );
					if ( $is_custom && $is_checked ) {
						$is_custom = false;
					}
					?>
                    <label>
                        <input name="<?php echo $field['id']; ?>" type="radio"
                               value="<?php echo esc_attr( $structure['value'] ); ?>"
                               class="learn-press-course-base" <?php echo $is_checked; ?> />
						<?php echo $structure['text']; ?>
                        <p><code><?php echo $structure['code']; ?></code></p>
                    </label>
                </li>
			<?php endforeach; ?>
            <li class="learn-press-single-course-permalink custom-base">
                <label>
                    <input name="<?php echo $field['id']; ?>"
                           id="learn_press_custom_permalink" type="radio"
                           value="custom" <?php checked( $is_custom, true ); ?> />
					<?php _e( 'Custom Base', 'learnpress' ); ?>
                    <input name="course_permalink_structure" id="course_permalink_structure" <?php if ( ! $is_custom ) {
						echo 'readonly="readonly"';
					} ?> type="text" value="<?php if ( $course_permalink ) {
						echo esc_attr( trailingslashit( $course_permalink ) );
					} ?>" class="regular-text code"/>
                </label>

                <p class="description"><?php _e( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default values instead.', 'learnpress' ); ?></p>
            </li>
        </ul>
		<?php
		return ob_get_clean();
	}

	public static function sanitize_meta( $meta ) {
		return $meta;
	}
}