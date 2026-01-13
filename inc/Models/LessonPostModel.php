<?php

/**
 * Class Lesson Post Model
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.7.6
 */

namespace LearnPress\Models;

use Exception;
use LP_Cache;
use LP_Post_Type_Filter;

class LessonPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_LESSON_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_DURATION = '_lp_duration';
	const META_KEY_PREVIEW  = '_lp_preview';

	/**
	 * Get post assignment by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post            = new LP_Post_Type_Filter();
		$filter_post->ID        = $post_id;
		$filter_post->post_type = LP_LESSON_CPT;

		$key_cache     = "lessonPostModel/find/{$post_id}";
		$lpLessonCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$lessonPostModel = $lpLessonCache->get_cache( $key_cache );
			if ( $lessonPostModel instanceof self ) {
				return $lessonPostModel;
			}
		}

		$lessonPostModel = self::get_item_model_from_db( $filter_post );
		// Set cache
		if ( $lessonPostModel instanceof LessonPostModel ) {
			$lpLessonCache->set_cache( $key_cache, $lessonPostModel );
		}

		return $lessonPostModel;
	}

	/**
	 * Get duration lesson
	 *
	 * @return string
	 */
	public function get_duration(): string {
		return $this->get_meta_value_by_key( self::META_KEY_DURATION, '0 minute' );
	}

	/**
	 * Check lesson enable preview
	 *
	 * @return bool
	 */
	public function has_preview(): bool {
		return $this->get_meta_value_by_key( self::META_KEY_PREVIEW, 'no' ) === 'yes';
	}

	/**
	 * Set lesson is preview or not
	 *
	 * @param bool $enable
	 *
	 * @return void
	 * @throws Exception
	 * @version 1.0.1
	 * @since 4.2.8.6
	 */
	public function set_preview( bool $enable = true ) {
		$this->save_meta_value_by_key( LessonPostModel::META_KEY_PREVIEW, $enable ? 'yes' : 'no' );
	}
}
