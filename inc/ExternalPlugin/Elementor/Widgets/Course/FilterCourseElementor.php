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
use LearnPress\TemplateHooks\TemplateAJAX;
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
		$settings 	= $this->get_settings_for_display();
		$id_el_target = 'lp-' . $this->get_id();
		$wrapper 	= sprintf( '<div id="%s"></div>', $id_el_target );
		$args = [
			'el_target' => '#' . $id_el_target,
			'params_url' => lp_archive_skeleton_get_args(),
		];

		$args = $args + $settings;

		$callback = [
			'class' => FilterCourseElementor::class,
			'method' => 'html_content',
		];

		echo TemplateAJAX::load_content_via_ajax( $wrapper, $args, $callback );	
	}

	public static function html_content( $settings = [] ) {
		try {

			$filter   	= FilterCourseTemplate::instance();
			$extraClass = '';
			if (empty($settings['item_filter'])) {
				return;
			}

			if ($settings['enable_filter_button'] == 'yes') {
				$extraClass = 'lp-filter-popup';
			}
			// button mobile and button style popup
			ob_start();
			self::button_popup_mobile($settings, $extraClass);
			$button_popup_mobile = ob_get_clean();

			$html_wrapper = [
				'' . $button_popup_mobile . '<form class="lp-form-course-filter ' . esc_attr($extraClass) . '">' => '</form><div class="filter-bg"></div>',
			];
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
			$m = Template::instance()->nest_elements($html_wrapper, ob_get_clean());
			
			$ob = new \stdClass();
			$ob->content = $m;
			return $ob;
		} catch (Throwable $e) {
			ob_end_clean();
			error_log(__METHOD__ . ': ' . $e->getMessage());
		}
	}

	protected static function button_popup_mobile( $settings, $extraClass)
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
}
