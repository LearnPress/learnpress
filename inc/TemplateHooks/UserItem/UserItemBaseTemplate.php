<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;
use LP_Course_Filter;
use LP_User;
use LP_User_Item;
use LP_User_Item_Course;
use Throwable;
use WP_Query;

class UserItemBaseTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {

	}

	/*public function add_internal_style_to_head() {
		echo '<style id="123123" type="text/css">body{background: red !important;}</style>';
	}*/

	/**
	 * Get display name html of instructor.
	 *
	 * @param LP_User_Item $user_item
	 *
	 * @return string
	 */
	public function html_start_date( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item ' . $user_item->_item_type . '">' => '</span>',
			];

			$start_date = $user_item->get_start_time();
			$content    = Template::instance()->nest_elements( $html_wrapper, $start_date );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}


}
