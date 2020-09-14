<?php
/**
 * Class LP_Course_Utils
 */
class LP_Course_Utils {

	/**
	 * Get section data from cache.
	 *
	 * @param int    $course_id
	 * @param string $return
	 *
	 * @return false|mixed
	 * @since 4.0.0
	 */
	public static function get_cached_db_sections( $course_id, $return = '' ) {
		if ( $return === 'ids' ) {
			return LP_Object_Cache::get( $course_id, 'learn-press/course-sections-ids' );

		}

		return LP_Object_Cache::get( 'course-' . $course_id, 'learn-press/course-sections' );
	}

	/**
	 * Set section data to cache.
	 *
	 * @param $course_id
	 * @param $sections
	 *
	 * @return bool
	 * @since 4.0.0
	 */
	public static function set_cache_db_sections( $course_id, $sections ) {
		if ( ! $sections ) {
			LP_Object_Cache::delete( $course_id, 'learn-press/course-sections' );
			LP_Object_Cache::delete( $course_id, 'learn-press/course-sections-ids' );

			return false;
		}

		LP_Object_Cache::set( 'course-' . $course_id, $sections, 'learn-press/course-sections' );
		LP_Object_Cache::set( $course_id, wp_list_pluck( $sections, 'section_id' ), 'learn-press/course-sections-ids' );

		return true;
	}

	public static function get_cached_section( $section_id ) {
		return LP_Object_Cache::get( $section_id, 'learn-press/course-sections-objects' );
	}

	public static function set_cached_section( $section_id, $section_object ) {
		if ( $section_object === false ) {
			return LP_Object_Cache::delete( $section_id, 'learn-press/course-sections-objects' );
		}
		LP_Object_Cache::set( $section_id, $section_object, 'learn-press/course-sections-objects' );

		return true;
	}

	/**
	 * Return ids of all items inside a course from cache.
	 *
	 * @param int $course_id
	 *
	 * @return false|mixed
	 */
	public static function get_course_items( $course_id ) {
		return LP_Object_Cache::get( $course_id, 'learn-press/course-item-ids' );
	}

	/**
	 * Set ids of all items read from db of a course to cache.
	 *
	 * @param int   $course_id
	 * @param array $items
	 */
	public static function set_course_items( $course_id, $items ) {
		LP_Object_Cache::set( $course_id, $items, 'learn-press/course-item-ids' );
	}

	public static function set_course_item_types( $course_id, $items ) {
		LP_Object_Cache::set( 'course-' . $course_id, $items, 'learn-press/course-item-types' );
	}

	public static function get_course_item_types( $course_id ) {
		return LP_Object_Cache::get( 'course-' . $course_id, 'learn-press/course-item-types' );
	}

	public static function set_course_items_group_types( $course_id, $items ) {
		LP_Object_Cache::set( 'course-' . $course_id, $items, 'learn-press/course-item-group-types' );
	}

	public static function get_course_items_group_types( $course_id ) {
		return LP_Object_Cache::set( 'course-' . $course_id, 'learn-press/course-item-group-types' );
	}

	public static function set_section_items( $section_id, $items ) {
		LP_Object_Cache::set( 'section-' . $section_id, $items, 'learn-press/section-items' );
	}

	public static function get_section_items( $section_id ) {
		return LP_Object_Cache::set( 'section-' . $section_id, 'learn-press/section-items' );
	}
}
