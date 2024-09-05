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
use LP_Request;
use Throwable;

class FilterCourseElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Filter Course', 'learnpress' );
		$this->name     = 'filter_course';
		$this->keywords = [ 'filter course' ];
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
		wp_enqueue_script( 'lp-course-filter' );
		parent::__construct( $data, $args );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->controls = Config::instance()->get(
			'filter-course-el',
			'elementor/course'
		);
		parent::register_controls();
	}

	protected function render() {
		try {
			$settings   = $this->get_settings_for_display();
			$filter     = FilterCourseTemplate::instance();
			$extraClass = '';
			if ( empty( $settings['item_filter'] ) ) {
				return;
			}

			if ( $settings['enable_filter_button'] === 'yes' ) {
				$extraClass = 'lp-filter-popup';
			}
			self::button_popup( $settings, $extraClass );

			if ( $settings['filter_selected_list'] === 'yes' ) {
				echo '<div class="selected-list">';
				self::selected_style_list();
				echo '</div>';
			}

			if ( ( ! empty( $_GET['term_id'] ) || ! empty( $_GET['tag_id'] )
			|| ! empty( $_GET['sort_by'] ) || ! empty( $_GET['c_level'] )
			|| ! empty( $_GET['c_authors'] ) ) && $settings['filter_selected_list'] === 'yes' ) {
				echo $filter->html_btn_reset();
			}

			$html_wrapper = apply_filters(
				'learn-press/filter-courses/sections/wrapper',
				[
					'<form class="lp-form-course-filter ' . esc_attr( $extraClass ) . '">' => '</form><div class="filter-bg"></div>',
				],
				$settings
			);
			$sections     = [];

			foreach ( $settings['item_filter'] as $field ) {
				$extraClassItem = '';
				$icon_toggle    = '';
				$wrapper        = '';
				$wrapper_end    = '';

				if ( isset( $field['enable_count'] ) && $field['enable_count'] !== 'yes' ) {
					$extraClassItem .= ' hide-count';
				}

				if ( $field['enable_heading'] !== 'yes' ) {
					$extraClassItem .= ' hide-title';
				}

				if ( $field['toggle_content'] === 'yes' ) {
					$extraClassItem .= ' toggle-content';
					$icon_toggle     = '<i class="icon-toggle-filter lp-icon-angle-up"></i><i class="icon-toggle-filter lp-icon-angle-down"></i>';

					if ( $field['default_toggle_on'] === 'yes' ) {
						$extraClassItem .= ' toggle-on';
					}
				}

				if ( $extraClassItem !== '' || $icon_toggle !== '' ) {
					$wrapper     = '<div class="' . esc_attr( $extraClassItem ) . '"> ' . $icon_toggle;
					$wrapper_end = '</div>';
				}

				$fields = array_merge(
					[ 'params_url' => lp_archive_skeleton_get_args() ],
					$settings
				);

				if ( is_callable( array( $filter, 'html_' . $field['item_fields'] ) ) ) {
					$sections[ $field['item_fields'] ] = [
						'wrapper'     => $wrapper,
						'text_html'   => $filter->{'html_' . $field['item_fields']}( $fields ),
						'wrapper_end' => $wrapper_end,
					];
				}
			}

			ob_start();
			Template::instance()->print_sections( $sections );
			echo Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	protected function button_popup( $settings, $extraClass ) {
		$text_popup = $settings['text_filter_button'] ?? esc_html__( 'Filter', 'learnpress' );

		echo '<button class="lp-button-popup ' . esc_attr( $extraClass ) . '">';
		if ( ! empty( $settings['icon_filter_button'] ) ) {
			Icons_Manager::render_icon(
				$settings['icon_filter_button'],
				array(
					'aria-hidden' => 'true',
					'class'       => 'icon-align-' . esc_attr( $settings['icon_position'] ),
				)
			);
		}
		echo $text_popup;
		if ( $settings['filter_selected_number'] === 'yes' ) {
			self::selected_style_number();
		}
		echo '</button>';
	}

	protected function selected_style_number() {
		$cat    = 0;
		$tag    = 0;
		$price  = 0;
		$level  = 0;
		$author = 0;

		if ( ! empty( $_GET['term_id'] ) ) {
			$cat = count( explode( ',', $_GET['term_id'] ) );
		}
		if ( ! empty( $_GET['tag_id'] ) ) {
			$tag = count( explode( ',', $_GET['tag_id'] ) );
		}
		if ( ! empty( $_GET['sort_by'] ) ) {
			$price = count( explode( ',', $_GET['sort_by'] ) );
		}
		if ( ! empty( $_GET['c_level'] ) ) {
			$level = count( explode( ',', $_GET['c_level'] ) );
		}
		if ( ! empty( $_GET['c_authors'] ) ) {
			$author = count( explode( ',', $_GET['c_authors'] ) );
		}

		$total = $cat + $tag + $price + $level + $author;
		if ( ! empty( $total ) ) {
			echo '<span class="selected-filter">' . $total . '</span>';
		}
	}

	protected function selected_style_list() {
		$classListItem = 'selected-item';
		$icon_move     = '<i class="icon-remove-selected lp-icon-times"></i>';

		$term_ids_str = LP_Request::get_param( 'term_id', '' );
		if ( ! empty( $term_ids_str ) ) {
			$cats = explode( ',', $term_ids_str );
			$cats = array_map( 'absint', $cats );
			foreach ( $cats as $cat ) {
				echo sprintf(
					'<span class="%s" data-name="term_id" data-value="%s">%s%s</span>',
					esc_attr( $classListItem ),
					esc_attr( $cat ),
					get_term( $cat, LP_COURSE_CATEGORY_TAX )->name,
					$icon_move
				);
			}
		}

		$tag_ids_str = LP_Request::get_param( 'tag_id', '' );
		if ( ! empty( $tag_ids_str ) ) {
			$tags = explode( ',', $tag_ids_str );
			$tags = array_map( 'absint', $tags );
			foreach ( $tags as $tag ) {
				echo sprintf(
					'<span class="%s" data-name="tag_id" data-value="%s">%s%s</span>',
					esc_attr( $classListItem ),
					esc_attr( $tag ),
					get_term( $tag, LP_COURSE_TAXONOMY_TAG )->name,
					$icon_move
				);
			}
		}

		$sort_by = LP_Request::get_param( 'sort_by', '' );
		if ( ! empty( $sort_by ) ) {
			if ( $sort_by === 'on_free' ) {
				$label = __( 'Free', 'learnpress' );
			} else {
				$label = __( 'Paid', 'learnpress' );
			}

			echo sprintf(
				'<span class="%s" data-name="sort_by" data-value="%s">%s%s</span>',
				esc_attr( $classListItem ),
				esc_attr( $sort_by ),
				$label,
				$icon_move
			);
		}

		$level = LP_Request::get_param( 'c_level', '' );
		if ( ! empty( $level ) ) {
			$label = $level;
			if ( $_GET['c_level'] === 'all' ) {
				$label = __( 'All Levels', 'learnpress' );
			}

			echo sprintf(
				'<span class="%s" data-name="c_level" data-value="%s">%s%s</span>',
				esc_attr( $classListItem ),
				esc_attr( $label ),
				$level,
				$icon_move
			);
		}

		$authors_str = LP_Request::get_param( 'c_authors', '' );
		if ( ! empty( $authors ) ) {
			$authors = explode( ',', $authors_str );
			$authors = array_map( 'absint', $authors );
			foreach ( $authors as $author ) {
				$user = get_userdata( $author );
				echo sprintf(
					'<span class="%s" data-name="c_authors" data-value="%s">%s%s</span>',
					esc_attr( $classListItem ),
					esc_attr( $author ),
					$user->display_name,
					$icon_move
				);
			}
		}
	}
}
