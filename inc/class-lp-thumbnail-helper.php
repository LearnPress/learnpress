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
		if ( ! $course = learn_press_get_course( $course_id ) ) {
			return '';
		}

		$attr  = wp_parse_args(
			$attr,
			array(
				'alt' => $course->get_title()
			)
		);
		$image = '';

		if ( 'yes' !== LP()->settings->get( 'archive_course_thumbnail' ) && in_array( $size, learn_press_get_custom_thumbnail_sizes() ) ) {
			$size = '';
		}

		if ( has_post_thumbnail( $course_id ) ) {
			$image = get_the_post_thumbnail( $course_id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $course_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		}

		if ( ! $image ) {
			if ( 'course_thumbnail' == $size ) {
				$image = LP()->image( 'no-image.png' );
			} else {
				$image = LP()->image( 'placeholder-800x450' );
			}
			$image = sprintf( '<img src="%s" %s />', $image, '' );
		}

		// @deprecated
		$image = apply_filters( 'learn_press_course_image', $image, $course_id, $size, $attr );

		return $image;
	}

	/**
	 * @param LP_Abstract_Post_Data $object
	 *
	 * @return bool|string
	 */
	public function get_video_embed( &$object ) {
		$video_id   = $object->get_data( 'video_id' );
		$video_type = $object->get_data( 'video_type' );

		if ( ! $video_id || ! $video_type ) {
			return false;
		}

		$embed  = '';
		$height = $object->get_data( 'video_embed_height' );
		$width  = $object->get_data( 'video_embed_width' );

		if ( 'youtube' === $video_type ) {
			$embed = '<iframe width="' . $width . '" height="' . $height . '" '
			         . 'src="https://www.youtube.com/embed/' . $video_id . '" '
			         . 'frameborder="0" allowfullscreen></iframe>';

		} elseif ( 'vimeo' === $video_type ) {
			$embed = '<iframe width="' . $width . '" height="' . $height . '" '
			         . ' src="https://player.vimeo.com/video/' . $video_id . '" '
			         . 'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}

		return $embed;
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