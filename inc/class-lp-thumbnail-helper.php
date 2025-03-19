<?php

use LearnPress\Models\CourseModel;

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
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			return '';
		}

		$size_img_setting = LP_Settings::get_option( 'course_thumbnail_dimensions', [] );
		$size_img_send    = [
			$size_img_setting['width'] ?? 500,
			$size_img_setting['height'] ?? 300,
		];
		$image_url        = $courseModel->get_image_url( $size_img_send );
		$image            = sprintf(
			'<img src="%s" alt="%s">',
			esc_url_raw( $image_url ),
			$courseModel->get_title()
		);

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
