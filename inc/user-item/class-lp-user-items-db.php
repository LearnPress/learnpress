<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_User_Items_DB
 *
 * @since 3.2.8.6
 */
class LP_User_Items_DB extends LP_Database {
	protected static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get items by user_item_id | this is id where item_id = course_id
	 *
	 * @param int $user_item_id
	 *
	 * @return object|bool
	 */
	public function get_course_items_by_user_item_id( $user_item_id_by_course_id = 0 ) {
		$item_types     = learn_press_get_course_item_types();

		if ( is_user_logged_in() ) {
			$user_inner_join = "INNER JOIN {$this->wpdb->users} u ON u.ID = X.user_id";
		} else {
			$user_inner_join = '';
		}

		$query = $this->wpdb->prepare( "
			SELECT ui.* 
			FROM ( 
				SELECT user_id, item_id, MAX(user_item_id) max_id 
				FROM {$wpdb->learnpress_user_items} GROUP BY user_id, item_id
			 ) AS X
			INNER JOIN {$wpdb->learnpress_user_items} ui ON ui.user_id = X.user_id AND ui.item_id = X.item_id AND ui.user_item_id = X.max_id 
			{$user_inner_join} 
			INNER JOIN {$wpdb->posts} p ON p.ID = X.item_id 
			WHERE ui.parent_id = %d
			ORDER BY user_item_id ASC
		", $user_item_id_by_course_id );

		return $wpdb->get_results( $query );
	}
}

LP_Course_DB::getInstance();

