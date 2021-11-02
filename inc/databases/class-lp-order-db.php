<?php
/**
 * Class LP_Order_DB
 *
 * @author tungnx
 * @since 4.1.4
 */

defined( 'ABSPATH' ) || exit();

class LP_Order_DB extends LP_Database {
	private static $_instance;

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
	 * Get the latest LP Order id by user_id and course_id
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return null|string
	 * @since 4.1.4
	 * @author tungnx
	 * @version 1.0.0
	 */
	public function get_last_lp_order_id_of_user_course( int $user_id, int $course_id ) {
		$user_id_str = $this->wpdb->prepare( '%"%d"%', $user_id );

		$query = $this->wpdb->prepare(
			"SELECT p.ID FROM {$this->tb_posts} as p
			INNER join {$this->tb_postmeta} pm on p.ID = pm.post_id
			INNER join {$this->tb_lp_order_items} as oi on p.ID = oi.order_id
			INNER join {$this->tb_lp_order_itemmeta} as oim on oim.learnpress_order_item_id = oi.order_item_id
			WHERE post_type = %s
			AND pm.meta_key = %s
			AND (pm.meta_value = %d OR pm.meta_value LIKE '%s')
			AND oim.meta_key = %s
			AND oim.meta_value = %d
			ORDER BY p.ID DESC
			LIMIT 1
			",
			LP_ORDER_CPT,
			'_user_id',
			$user_id,
			$user_id_str,
			'_course_id',
			$course_id
		);

		return $this->wpdb->get_var( $query );
	}
}

