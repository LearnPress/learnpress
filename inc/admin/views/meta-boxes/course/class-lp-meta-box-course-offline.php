<?php
/**
 * LP_Meta_Box_Course_Offline
 *
 * Hide some tabs, fields no need for mode offline.
 */

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;

class LP_Meta_Box_Course_Offline {
	use Singleton;

	public function init() {
		add_filter( 'learnpress/course/metabox/tabs', [ $this, 'hide_tabs_when_enable_offline' ], 9999, 2 );
		add_filter( 'lp/course/meta-box/fields/general', [
			$this,
			'hide_fields_general_when_enable_offline'
		], 9999, 2 );
		add_filter( 'lp/course/meta-box/fields/price', [
			$this,
			'hide_fields_price_when_enable_offline'
		], 9999, 2 );
	}

	/**
	 * Hide tabs when enable offline course
	 *
	 * @param array $tabs
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function hide_tabs_when_enable_offline( $tabs, $post_id ) {
		$course                   = CourseModel::find( $post_id, true );
		$is_enable_offline_course = false;
		if ( $course instanceof CourseModel ) {
			$is_enable_offline_course = $course->is_offline();
		}

		if ( ! $is_enable_offline_course ) {
			return $tabs;
		}

		$tabs_hide = [
			'assessment',
			'certificates',
			'content_drip',
		];

		foreach ( $tabs_hide as $tab_hide ) {
			if ( ! isset( $tabs[ $tab_hide ] ) ) {
				continue;
			}
			unset( $tabs[ $tab_hide ] );
		}

		return $tabs;
	}


	public function hide_fields_general_when_enable_offline( $fields, $post_id ) {
		$course                   = CourseModel::find( $post_id, true );
		$is_enable_offline_course = false;
		if ( $course instanceof CourseModel ) {
			$is_enable_offline_course = $course->is_offline();
		}

		if ( ! $is_enable_offline_course ) {
			return $fields;
		}

		$fields_hide = [
			'_lp_block_expire_duration',
			'_lp_block_finished',
			'_lp_allow_course_repurchase',
			'_lp_course_repurchase_option',
			'_lp_retake_count',
			'_lp_has_finish',
		];

		foreach ( $fields_hide as $field_hide ) {
			if ( ! isset( $fields[ $field_hide ] ) ) {
				continue;
			}
			unset( $fields[ $field_hide ] );
		}

		return $fields;
	}

	public function hide_fields_price_when_enable_offline( $fields, $post_id ) {
		$course                   = CourseModel::find( $post_id, true );
		$is_enable_offline_course = false;
		if ( $course instanceof CourseModel ) {
			$is_enable_offline_course = $course->is_offline();
		}

		if ( ! $is_enable_offline_course ) {
			return $fields;
		}

		$fields_hide = [
			'_lp_no_required_enroll',
		];

		foreach ( $fields_hide as $field_hide ) {
			if ( ! isset( $fields[ $field_hide ] ) ) {
				continue;
			}
			unset( $fields[ $field_hide ] );
		}

		return $fields;
	}
}

LP_Meta_Box_Course_Offline::instance();
