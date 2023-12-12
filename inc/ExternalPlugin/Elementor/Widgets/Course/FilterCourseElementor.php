<?php

/**
 * Class FilterCourseElementor
 *
 * @sicne 4.2.5
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use Elementor\Icons_Manager;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use Throwable;
use WP_Term;

class FilterCourseElementor extends LPElementorWidgetBase
{
	public function __construct($data = [], $args = null)
	{
		$this->title    = esc_html__('Filter Course', 'learnpress');
		$this->name     = 'filter_course';
		$this->keywords = ['filter course'];
		$this->icon     = 'eicon-taxonomy-filter';

		wp_register_style(
			'lp-course-filter-el',
			LP_PLUGIN_URL . 'assets/css/elementor/course/filter-course.css',
			array(),
			uniqid()
		);

		wp_register_script(
			'lp-course-filter-el',
			LP_PLUGIN_URL . 'assets/js/dist/elementor/course-filter.js',
			array(),
			uniqid(),
			true
		);

		$this->add_style_depends( 'lp-course-filter-el' );
		$this->add_script_depends( 'lp-course-filter-el' );
		wp_enqueue_script('lp-course-filter');
		parent::__construct($data, $args);
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls()
	{
		$this->controls = Config::instance()->get(
			'filter-course-el',
			'elementor/course'
		);
		parent::register_controls();
	}

	protected function render()
	{
		try {
			$settings 	= $this->get_settings_for_display();
			$filter   	= FilterCourseTemplate::instance();
			$extraClass = '';
			if (empty($settings['item_filter'])) {
				return;
			}

			if ($settings['enable_filter_button'] == 'yes') {
				$extraClass = 'lp-filter-popup';
			}
			echo self::button_popup($settings, $extraClass);

			if ($settings['filter_selected_list'] == 'yes') {
				echo '<div class="selected-list">';
				echo self::selected_style_list();
				echo '</div>';
			}
	
			if ((!empty($_GET['term_id']) || !empty($_GET['tag_id']) || !empty($_GET['sort_by']) || !empty($_GET['c_level']) || !empty($_GET['c_authors'])) && $settings['filter_selected_list'] == 'yes') {
				echo $filter->html_btn_reset();
			}

			$html_wrapper = apply_filters(
				'learn-press/filter-courses/sections/wrapper',
				[
					'<form class="lp-form-course-filter ' . esc_attr($extraClass) . '">' => '</form><div class="filter-bg"></div>',
				],
				$settings
			);
			$sections     = [];

			foreach ($settings['item_filter'] as $field) {
				$extraClassItem = $icon_toggle = $wrapper = $wrapper_end = $custom_heading = '';

				if (isset($field['enable_count']) && $field['enable_count'] != 'yes') {
					$extraClassItem .= ' hide-count';
				}

				if ($field['enable_heading'] != 'yes') {
					$extraClassItem .= ' hide-title';
				}

				if ($field['toggle_content'] == 'yes') {
					$extraClassItem .= ' toggle-content';
					$icon_toggle = '<i class="icon-toggle-filter fas fa-angle-up"></i><i class="icon-toggle-filter fas fa-angle-down"></i>';

					if ($field['default_toggle_on'] == 'yes') {
						$extraClassItem .= ' toggle-on';
					}
				}

				if ($extraClassItem != '' || $icon_toggle != '' || $custom_heading != '') {
					$wrapper 		= '<div class="' . esc_attr($extraClassItem) . '"> ' . $icon_toggle . '' . $custom_heading . '';
					$wrapper_end 	= '</div>';
				}

				$fields = array_merge(
					[ 'params_url' => lp_archive_skeleton_get_args() ],
					$settings
				);

				if (is_callable(array($filter, 'html_' . $field['item_fields']))) {
					$sections[$field['item_fields']] = [
						'wrapper' => $wrapper,
						'text_html' => $filter->{'html_' . $field['item_fields']}($fields),
						'wrapper_end' => $wrapper_end,
					];
				}
			}

			ob_start();
			Template::instance()->print_sections($sections);
			echo Template::instance()->nest_elements($html_wrapper, ob_get_clean());
		} catch (Throwable $e) {
			ob_end_clean();
			error_log(__METHOD__ . ': ' . $e->getMessage());
		}
	}

	protected function button_popup($settings, $extraClass)
	{
		$text_popup = $settings['text_filter_button'] ?? esc_html__('Filter', 'learnpress');

		echo '<button class="lp-button-popup ' . esc_attr($extraClass) . '">';
		if (!empty($settings['icon_filter_button'])) {
			Icons_Manager::render_icon(
				$settings['icon_filter_button'],
				array(
					'aria-hidden' => 'true',
					'class'       => 'icon-align-' . esc_attr($settings['icon_position']),
				)
			);
		}
		echo $text_popup;
		if ($settings['filter_selected_number'] == 'yes') {
			echo self::selected_style_number();
		}
		echo '</button>';
	}

	protected function selected_style_number()
	{
		$cat = $tag = $price = $level = $author = 0;
		$total = '';

		if (!empty($_GET['term_id'])) {
			$cat =  count(explode(',', $_GET['term_id']));
		}
		if (!empty($_GET['tag_id'])) {
			$tag =  count(explode(',', $_GET['tag_id']));
		}
		if (!empty($_GET['sort_by'])) {
			$price =  count(explode(',', $_GET['sort_by']));
		}
		if (!empty($_GET['c_level'])) {
			$level =  count(explode(',', $_GET['c_level']));
		}
		if (!empty($_GET['c_authors'])) {
			$author =  count(explode(',', $_GET['c_authors']));
		}
		$total = $cat + $tag + $price + $level + $author;

		if (!empty($total)) {
			echo '<span class="selected-filter">' . $total . '</span>';
		}
	}

	protected function selected_style_list()
	{
		$cats = $tags = $authors = '';
		$classListItem = 'selected-item';
		$icon_move = '<i class="icon-remove-selected fas fa-times"></i>';

		if (!empty($_GET['term_id'])) {
			$cats = explode(',', $_GET['term_id']);
			foreach ($cats as $cat) {
				echo '<span class="' . $classListItem . '" data-name="term_id" data-value="' . $cat . '">' . get_term($cat, 'course_category')->name . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['tag_id'])) {
			$tags = explode(',', $_GET['tag_id']);
			foreach ($tags as $tag) {
				echo '<span class="' . $classListItem . '" data-name="tag_id" data-value="' . $tag . '">' . get_term($tag, 'course_tag')->name . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['sort_by'])) {
			if ($_GET['sort_by'] == 'on_free') {
				echo  '<span class="' . $classListItem . '" data-name="sort_by" data-value="' . $_GET['sort_by'] . '">' . __('Free', 'learnpress') . '' . $icon_move . '</span>';
			} else {
				echo '<span class="' . $classListItem . '" data-name="sort_by" data-value="' . $_GET['sort_by'] . '">' . __('Paid', 'learnpress') . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['c_level'])) {
			if ($_GET['c_level'] == 'all') {
				echo  '<span class="' . $classListItem . '" data-name="c_level" data-value="' . $_GET['c_level'] . '">' . __('All Levels', 'learnpress') . '' . $icon_move . '</span>';
			} else {
				echo  '<span class="' . $classListItem . '" data-name="c_level" data-value="' . $_GET['c_level'] . '">' . $_GET['c_level'] . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['c_authors'])) {
			$authors = explode(',', $_GET['c_authors']);
			foreach ($authors as $author) {
				$user = get_userdata($author);
				echo  '<span class="' . $classListItem . '" data-name="c_authors" data-value="' . $_GET['c_authors'] . '">' . $user->display_name . '' . $icon_move . '</span>';
			}
		}
	}
}
