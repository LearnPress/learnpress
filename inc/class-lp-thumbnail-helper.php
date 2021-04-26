<?php

/**
 * Class LP_Thumbnail_Helper
 *
 * @since 3.0.11
 */
class LP_Thumbnail_Helper {

	/**
	 * @var LP_Thumbnail_Helper
	 */
	protected static $instance = null;

	/**
	 * LP_Thumbnail_Helper constructor.
	 */
	protected function __construct() {
	}

	/**
	 * @param int    $course_id
	 * @param string $size
	 * @param array  $attr
	 *
	 * @return string
	 */
	public function get_course_image( $course_id, $size = 'course_thumbnail', $attr = array() ) {
		$course = learn_press_get_course( $course_id );

		if ( ! $course ) {
			return '';
		}

		$attr  = wp_parse_args(
			$attr,
			array(
				'alt'   => $course->get_title(),
				'title' => $course->get_title(),
			)
		);
		$image = '';

		$thumbnail = LP()->settings()->get( 'course_thumbnail_dimensions' );
		if ( ! empty( $thumbnail['0'] ) && ! empty( $thumbnail['1'] ) ) {
			$size = array( $thumbnail['0'], $thumbnail['1'] );
		}

		if ( isset( $thumbnail['width'] ) && isset( $thumbnail['height'] ) ) {
			$size = array( $thumbnail['width'], $thumbnail['height'] );
		}

		$parent_id = wp_get_post_parent_id( $course_id );

		if ( has_post_thumbnail( $course_id ) ) {
			$image = get_the_post_thumbnail( $course_id, $size, $attr );
		} elseif ( $parent_id && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		}

		if ( ! $image ) {
			$image = LP()->image( 'no-image.png' );
			$image = sprintf(
				'<img src="%s" alt="%s">', esc_url( $image ),
				_x( 'course thumbnail', 'no course thumbnail', 'learnpress' )
			);
		}

		// @deprecated
		$image = apply_filters( 'learn_press_course_image', $image, $course_id, $size, $attr );

		return $image;
	}

	/**
	 * @return LP_Thumbnail_Helper
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

return LP_Thumbnail_Helper::instance();
