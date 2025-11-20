<?php
/**
 * Class ProfileCoursesTemplate.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Profile;

class ProfileCoursesTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/profile/layout/courses', [ $this, 'layout' ] );
	}

	/**
	 * @param array $args
	 */
	public function layout( array $args = [] ) {
		$courses_created_tab               = $args['courses_created_tab'] ?? [];
		$args_query_user_courses_created   = $args['args_query_user_courses_created'] ?? [];
		$args_query_user_courses_statistic = $args['args_query_user_courses_statistic'] ?? [];

		// Check permission to view courses tab.
		$profile       = LP_Profile::instance();
		$user_can_view = $profile->current_user_can( 'view-tab-courses' );
		if ( ! $user_can_view ) {
			return;
		}

		ob_start();
		lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' );
		$html_skeleton = ob_get_clean();

		$html_li_tabs         = '';
		$html_filter_contents = '';
		foreach ( $courses_created_tab as $key => $created ) {
			$html_li_tabs .= sprintf(
				'<li><a class="%s" data-tab="%s">%s</a></li>',
				esc_attr( $key === '' ? 'active' : '' ),
				esc_attr( $key === '' ? 'all' : $key ),
				esc_html( $created )
			);

			$html_filter_contents .= sprintf(
				'<div class="learn-press-course-tab__filter__content" data-tab="%s" style="%s">%s</div>',
				esc_attr( $key === '' ? 'all' : $key ),
				esc_attr( $key !== '' ? 'display: none' : '' ),
				$html_skeleton
			);
		}

		$section_tabs = [
			'wrap'     => '<div class="learn-press-tabs">',
			'ul'       => '<ul class="learn-press-filters">',
			'content'  => $html_li_tabs,
			'ul-end'   => '</ul>',
			'wrap-end' => '</div>',
		];

		$section_contents = [
			'wrap'     => '<div class="learn-press-profile-course__progress">',
			'content'  => $html_filter_contents,
			'input'    => sprintf(
				'<input class="lp_profile_tab_input_param"
						type="hidden" name="args_query_user_courses_created"
						value="%s">',
				sanitize_text_field( htmlentities( wp_json_encode( $args_query_user_courses_created ) ) )
			),
			'wrap-end' => '</div>',
		];

		$section_courses_statistic = apply_filters(
			'learn-press/profile/layout/courses/sections/statistic',
			[
				'wrap'     => '<div class="learn-press-profile-course__statistic">',
				'skeleton' => $html_skeleton,
				'input'    => sprintf(
					'<input type="hidden" name="args_query_user_courses_statistic" value="%s">',
					sanitize_text_field( htmlentities( wp_json_encode( $args_query_user_courses_statistic ) ) )
				),
				'wrap-end' => '</div>',
			],
			$args
		);

		$section_courses_list = apply_filters(
			'learn-press/profile/layout/courses/sections/course-list',
			[
				'wrap'       => '<div class="learn-press-profile-course__tab">',
				'filter'     => '<div class="learn-press-course-tab-created learn-press-course-tab-filters" data-tab="created">',
				'tabs'       => Template::combine_components( $section_tabs ),
				'progress'   => Template::combine_components( $section_contents ),
				'filter-end' => '</div>',
				'wrap-end'   => '</div>',
			],
			$args
		);

		$section = apply_filters(
			'learn-press/profile/layout/courses/sections',
			[
				'wrap'        => '<div class="learn-press-profile-course__tab">',
				'statistic'   => Template::combine_components( $section_courses_statistic ),
				'course-list' => Template::combine_components( $section_courses_list ),
				'wrap-end'    => '</div>',
			],
			$args
		);

		echo Template::combine_components( $section );
	}
}
