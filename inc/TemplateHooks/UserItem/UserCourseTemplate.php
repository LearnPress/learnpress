<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Helpers\Template;
use LP_User_Item_Course;

class UserCourseTemplate extends UserItemBaseTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {

	}
}
