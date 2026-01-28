<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_Course_Template
 *
 * Groups templates related course and items
 *
 * @since 3.x.x
 */
class LP_Template_General extends LP_Abstract_Template {

	/*public function filter_block_content_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( $template_name == 'global/block-content.php' ) {
			$can_view_item = false;

			if ( ! is_user_logged_in() ) {
				$can_view_item = 'not-logged-in';
			} elseif ( ! learn_press_current_user_enrolled_course() ) {
				$can_view_item = 'not-enrolled';
			}
			learn_press_get_template( 'single-course/content-protected.php', array( 'can_view_item' => $can_view_item ) );

			return false;
		}

		return $located;

	}*/

	public function term_conditions_template() {
		$page_id = learn_press_get_page_id( 'term_conditions' );
		if ( $page_id ) {
			$page_link = get_page_link( $page_id );
			learn_press_get_template( 'checkout/term-conditions.php', array( 'page_link' => $page_link ) );
		}
	}

	public function breadcrumb( $args = array() ) {
		echo Template::html_breadcrumb();
	}

	/**
	 * Get header for course page
	 */
	public function template_header() {
		get_header( 'course' );
	}

	/**
	 * Get header for course page
	 */
	public function template_footer() {
		get_footer( 'course' );
	}
}

return new LP_Template_General();
