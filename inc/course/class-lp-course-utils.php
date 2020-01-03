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
}