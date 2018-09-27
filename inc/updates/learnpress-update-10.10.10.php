<?php
/**
 * Sync data for newest version
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_101010
 */
class LP_Update_101010 extends LP_Update_Base {

	/**
	 * @var int
	 */
	protected $batch_items = 2;

	/**
	 * LP_Update_101010 constructor.
	 */
	public function __construct() {
		$this->version = '10.10.10';
		$this->steps   = array(
			'sync_course_orders',
			'sync_user_orders',
			'sync_course_final_quiz',
			'calculate_user_course_results',
			//'remove_older_post_meta'
		);
		add_action( 'learn-press/update-completed', array( $this, 'update_completed' ) );
		parent::__construct();
	}

	public function update_completed( $version ) {
		if ( version_compare( $this->version, $version, '=' ) ) {
			update_option( 'learnpress_data_synced', 'yes' );
		}
	}

	public function calculate_user_course_results() {
		$api = LP_Repair_Database::instance();
		$key = 'learnpress_calculate_user_course_results';

		if ( ! learn_press_has_option( $key ) ) {
			global $wpdb;

			$query = $wpdb->prepare( "
                    SELECT ID
                    FROM {$wpdb->users}
                    WHERE 1
                ", 1 );

			$users = $wpdb->get_col( $query );

			update_option( $key, (array) $users );
		}

		if ( ! ( $users = get_option( $key ) ) ) {
			return true;
		}

		$users      = (array) $users;
		$exec_users = array_splice( $users, 0, $this->batch_items );
		update_option( $key, $users );

		$api->calculate_course_results( $exec_users );

		return false;
	}

	/**
	 * @return bool
	 */
	public function sync_course_orders() {

		$api = LP_Repair_Database::instance();
		$key = 'learnpress_sync_course_orders';

		if ( ! learn_press_has_option( $key ) ) {
			$courses = $api->get_all_courses();
			update_option( $key, (array) $courses );
		}

		if ( ! ( $courses = get_option( $key ) ) ) {
			return true;
		}

		$courses         = (array) $courses;
		$execute_courses = array_splice( $courses, 0, $this->batch_items );
		update_option( $key, $courses );

		$api->sync_course_orders( $execute_courses );

		return false;
	}

	public function sync_user_orders() {
		global $wpdb;

		$api = LP_Repair_Database::instance();
		$key = 'learnpress_sync_user_orders';

		if ( ! learn_press_has_option( $key ) ) {
			$query = $wpdb->prepare( "
                    SELECT ID
                    FROM {$wpdb->users}
                    WHERE 1
                ", 1 );

			$users = $wpdb->get_col( $query );
			update_option( $key, (array) $users );
		}

		if ( ! ( $users = get_option( $key ) ) ) {
			return true;
		}

		$users         = (array) $users;
		$execute_users = array_splice( $users, 0, $this->batch_items );
		update_option( $key, $users );
		$api->sync_user_orders( $execute_users );

		return false;
	}

	public function sync_course_final_quiz() {
		$api = LP_Repair_Database::instance();
		$key = 'learnpress_sync_course_final_quiz';

		if ( ! learn_press_has_option( $key ) ) {
			$courses = $api->get_all_courses();
			update_option( $key, (array) $courses );
		}

		if ( ! ( $courses = get_option( $key ) ) ) {
			return true;
		}

		$courses         = (array) $courses;
		$execute_courses = array_splice( $courses, 0, $this->batch_items );
		update_option( $key, $courses );

		$api->sync_course_final_quiz( $execute_courses );

		return false;
	}

	public function remove_older_post_meta() {
		$api = LP_Repair_Database::instance();
		$api->remove_older_post_meta();

		return true;
	}
}

$updater = new LP_Update_101010();
$return  = $updater->update( LP_Request::get( 'force' ) == 'true' );

return array( 'done' => $return, 'percent' => $updater->get_percent() );