<?php

/**
 * Class LP_Repair_Database
 *
 * Repair database tool
 *
 * @since 3.1.0
 */
class LP_Repair_Database {
	/**
	 * @var LP_Repair_Database
	 */
	protected static $instance = null;

	protected $deleting_posts = array();

	/**
	 * LP_Repair_Database constructor.
	 *
	 * @access protected
	 */
	protected function __construct() {
		//add_action( 'save_post', array( $this, 'save_post' ), 0 );
		//add_action( 'deleted_post', array( $this, 'save_post' ), 0 );
		//add_action( 'learn-press/added-item-to-section', array( $this, 'added_item_to_section' ), 5000, 3 );
		//add_action( 'learn-press/removed-item-from-section', array( $this, 'removed_item_from_course' ), 5000, 2 );
		add_action( 'learn-press/save-course', array( $this, 'save_course' ), 5000, 1 );
		add_action( 'learn-press/added-course-item', array( $this, 'added_course_item' ), 10, 2 );
		add_action( 'learn-press/removed-course-item', array( $this, 'removed_course_item' ), 10, 2 );
		add_action( 'learn-press/transition-course-item-status', array(
			$this,
			'transition_course_item_status'
		), 10, 4 );

		add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );
		add_action( 'deleted_post', array( $this, 'deleted_post' ) );
	}

	public function before_delete_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		$data      = array(
			'post_type' => $post_type
		);

		switch ( $post_type ) {
			case LP_ORDER_CPT:

				$order         = learn_press_get_order( $post_id );
				$data['users'] = $order->get_users();

				break;
		}

		$this->deleting_posts[ $post_id ] = $data;

	}

	public function deleted_post( $post_id ) {
		try {
			if ( ! empty( $this->deleting_posts[ $post_id ] ) ) {
				$data      = $this->deleting_posts[ $post_id ];
				$post_type = ! empty( $data['post_type'] ) ? $data['post_type'] : '';

				switch ( $post_type ) {
					case LP_ORDER_CPT:
						$this->remove_order_from_user_meta( $post_id, $data );
						break;
				}
			}
		}
		catch ( Exception $ex ) {
			echo $ex->getMessage();
		}

	}

	public function remove_child_orders(){

	}

	public function remove_order_from_user_meta( $order_id, $data ) {
		if ( ! empty( $data['users'] ) ) {
			foreach ( $data['users'] as $user_id ) {
				$user_orders = get_user_meta( $user_id, 'orders', true );

				if ( $user_orders ) {
					foreach ( $user_orders as $course_id => $course_orders ) {
						$course_orders = array_unique( $course_orders );
						if ( false !== ( $in_pos = array_search( $order_id, $course_orders ) ) ) {
							unset( $course_orders[ $in_pos ] );
						}

						if ( ! $course_orders ) {
							unset( $user_orders[ $course_id ] );
						} else {
							$user_orders[ $course_id ] = $course_orders;
						}

					}
				}

				update_user_meta( $user_id, 'order', $user_orders );
			}
		}
	}

	public function save_course( $course_id ) {
		$this->sync_course_data( $course_id );
	}

	public function removed_course_item( $item_id, $course_id ) {
		$this->sync_course_data( $course_id );
		$this->remove_user_item( $item_id );
	}

	/**
	 * @param int $item_id
	 * @param int $course_id
	 */
	public function added_course_item( $item_id, $course_id ) {
		$this->sync_course_data( $course_id );
	}

	/**
	 * @param int $item_id
	 * @param int $course_id
	 */
	public function removed_item_from_course( $item_id, $course_id ) {
		$this->sync_course_data( $course_id );
	}

	public function transition_course_item_status( $item_id, $course_id, $old, $new ) {
		if ( $old === $new ) {
			return;
		}
		$this->sync_course_data( $course_id );
	}

	public function get_user_item_type( $item_id ) {
		global $wpdb;
		if ( ! $item_type = get_post_type( $item_id ) ) {
			$query     = $wpdb->prepare( "
				SELECT item_type
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id = %d
				LIMIT 0,1
			", $item_id );
			$item_type = $wpdb->get_var( $query );
		}

		return $item_type;
	}

	public function remove_user_item( $item_id ) {
		global $wpdb;

		$query = "
			DELETE items, meta
			FROM {$wpdb->learnpress_user_items} items
			INNER JOIN {$wpdb->learnpress_user_itemmeta} meta ON items.user_item_id = meta.learnpress_user_item_id
		";

		$where = "";

		if ( $this->get_user_item_type( $item_id ) === LP_COURSE_CPT ) {
			$_query = $wpdb->prepare( "
				SELECT user_item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id = %d 
				AND parent_id = 0
			", $item_id );

			$user_item_ids = $wpdb->get_col( $_query );
			$format        = array_fill( 0, sizeof( $user_item_ids ), '%d' );

			$where = $wpdb->prepare( "
				WHERE parent_id IN(" . join( ',', $format ) . ")
			", $user_item_ids );

			$where .= $wpdb->prepare( "AND ref_id = %d", $item_id );
		} else {
			$where = $wpdb->prepare( "item_id = %d", $item_id );
		}

		$query .= $where;
	}

	/**
	 * @param int $post_id
	 */
	public function save_post( $post_id ) {
		global $wpdb;
		$post_type   = get_post_type( $post_id );
		$action      = preg_replace( '!_post$!', '', current_action() );
		$course_curd = new LP_Course_CURD();
		$course_ids  = array();

		switch ( $post_type ) {
			case LP_COURSE_CPT:
				$course_ids = array( $post_id );
				break;
			default:

				// Course is support type of this item?
				if ( learn_press_is_support_course_item_type( $post_type ) ) {

					// Find it course
					$course_ids = $course_curd->get_course_by_item( $post_id );
				}
		}

		foreach ( $course_ids as $course_id ) {
			$this->sync_course_data( $course_id );
		}

//		LP_Background_Sync_Data::instance()->push_to_queue(
//			array(
//				'action' => 'sync_course_data',
//				'args'   => array( 'course_id' => $course_id )
//			)
//		);
	}

	/**
	 * Sync course data when saving post.
	 *
	 * @since 3.1.0
	 *
	 * @param int $course_id
	 */
	public function sync_course_data( $course_id ) {
		$user_curd   = new LP_User_CURD();
		$course_curd = new LP_Course_CURD();

		$count_items = 0;
		if ( $counts = $course_curd->count_items( $course_id ) ) {
			$count_items = array_sum( $counts );
		}

		update_post_meta( $course_id, 'count_items', $count_items );
		$this->queue_sync_user_course_results( $course_id );

	}

	/**
	 * Sync all
	 */
	public function sync_all() {
		$this->sync_course_orders();
		$this->sync_user_courses();
	}

	public function call( $func ) {
		$func = preg_replace( '~[-]+~', '_', $func );

		if ( ! is_callable( array( $this, $func ) ) ) {
			throw new Exception( sprintf( __( 'The method %s is not callable.', 'learnpress' ), $func ) );
		}

		$args = func_get_args();
		unset( $args[0] );

		return sizeof( $args ) ?
			call_user_func_array( array( $this, $func ), $args ) :
			call_user_func( array( $this, $func ) );
	}

	public function queue_sync_user_course_results( $course_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT DISTINCT user_id
			FROM {$wpdb->learnpress_user_items}
			WHERE item_id = %d
		", $course_id );

		if ( $user_ids = $wpdb->get_col( $query ) ) {
			$queue_user_ids = get_option( 'sync-user-course-results' );
			$first_time     = ! $queue_user_ids;

			if ( $first_time ) {
				$queue_user_ids = $user_ids;
			} else {
				settype( $queue_user_ids, 'array' );
				$queue_user_ids = array_merge( $queue_user_ids, $user_ids );
				$queue_user_ids = array_unique( $queue_user_ids );
			}
			$option_key = 'sync-user-course-results';
			update_option( $option_key, $queue_user_ids, 'no' );

			if ( $first_time || ! get_option( 'doing-sync-user-course-results' ) ) {
				$bg = LP_Background_Sync_Data::instance();
				$bg->is_safe( false );
				$bg->push_to_queue(
					array(
						'action'     => 'sync-user-course-results',
						'course_id'  => $course_id,
						'option_key' => $option_key
					)
				)->save();
				$bg->reset_safe();

				update_option( 'doing-sync-user-course-results', 'yes' );
			}
		}
	}

	/**
	 * Sync orders for each course
	 *
	 * @since 3.1.0
	 *
	 * @param array|string $courses
	 *
	 * @return bool|array
	 */
	public function sync_course_orders( $courses = '*' ) {
		global $wpdb;

		if ( empty( $courses ) ) {
			return false;
		}

		if ( $courses === '*' ) {
			$query = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status = %s
			", LP_COURSE_CPT, 'publish' );

			$courses = $wpdb->get_col( $query );

			if ( $courses ) {
				return false;
			}
		}

		$statuses = learn_press_get_order_statuses( true, true );
		settype( $courses, 'array' );

		$statuses_format = array_fill( 0, sizeof( $statuses ), '%s' );
		$courses_format  = array_fill( 0, sizeof( $courses ), '%d' );
		$statuses_format = $wpdb->prepare( join( ',', $statuses_format ), $statuses );
		$courses_format  = $wpdb->prepare( join( ',', $courses_format ), $courses );

		$query = $wpdb->prepare( "
				SELECT cid, status, orders
				FROM(
					SELECT oim.meta_value cid, concat(oim.meta_value, ' ', o.post_status)  a, post_status `status`, GROUP_CONCAT(o.ID) orders
					FROM {$wpdb->learnpress_order_itemmeta} oim 
					INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
					INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id 
					WHERE o.post_type = %s
					AND o.post_status IN ($statuses_format) 
					AND oim.meta_value IN ($courses_format)
					GROUP BY a, cid
				) X
			", '_course_id', 'lp_order' );

		foreach ( $courses as $course_id ) {
			foreach ( $statuses as $status ) {
				update_post_meta( $course_id, 'order-' . str_replace( 'lp-', '', $status ), array() );
			}
		}

		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				update_post_meta( $result->cid, 'order-' . str_replace( 'lp-', '', $result->status ), explode( ',', $result->orders ) );
			}
		}

		return $courses;
	}

	/**
	 * Sync orders for each user
	 *
	 * @param array $users
	 */
	public function sync_user_orders( $users = array() ) {
		global $wpdb;
		$api = new LP_User_CURD();
		LP_Debug::logTime( __FUNCTION__ );
		foreach ( $users as $user ) {
			if ( ! $orders = $api->read_orders( $user ) ) {
				continue;
			}

			$orders = array_unique( $orders );

			update_user_meta( $user, 'orders', $orders );
		}
		LP_Debug::logTime( __FUNCTION__ );

	}

	/**
	 * Sync courses for each user
	 *
	 * @since 3.1.0
	 */
	public function sync_user_courses() {
		//echo __FUNCTION__;

	}


	/**
	 * Sync final quiz for each course.
	 *
	 * @param array $courses
	 */
	public function sync_course_final_quiz( $courses = array() ) {
		settype( $courses, 'array' );
		foreach ( $courses as $course_id ) {

			if ( ! $course = learn_press_get_course( $course_id ) ) {
				continue;
			}

			/**
			 * If course result is not set to final-quiz
			 */
			if ( $course->get_data( 'course_result' ) !== 'evaluate_final_quiz' ) {
				delete_post_meta( $course_id, '_lp_final_quiz' );
				continue;
			}

			$items = $course->get_item_ids();
			if ( $items ) {
				$end = end( $items );
				if ( learn_press_get_post_type( $end ) === LP_QUIZ_CPT ) {
					$final_quiz = $end;
				}
			}

			if ( isset( $final_quiz ) ) {
				update_post_meta( $course_id, '_lp_final_quiz', $final_quiz );
			} else {
				delete_post_meta( $course_id, '_lp_final_quiz' );
				update_post_meta( $course_id, '_lp_course_result', 'evaluate_lesson' );
			}
		}
	}

	public function remove_older_post_meta() {
		global $wpdb;
		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s
		", $wpdb->esc_like( '_lpr_' ) . '%' );
		$wpdb->query( $query );

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s
		", '%' . $wpdb->esc_like( '_lpr_' ) . '%' );
		$wpdb->query( $query );
	}

	/**
	 * Get all ids of existing courses
	 *
	 * @return array
	 */
	public function get_all_courses() {
		global $wpdb;
		$query = $wpdb->prepare( "
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
                AND post_status = %s
        ", LP_COURSE_CPT, 'publish' );

		return $wpdb->get_col( $query );
	}

	/**
	 * @return LP_Repair_Database
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Repair_Database::instance();