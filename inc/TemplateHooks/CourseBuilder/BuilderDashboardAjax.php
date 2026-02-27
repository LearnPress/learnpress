<?php
/**
 * AJAX handler for Dashboard chart data in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use LP_REST_Admin_Statistics_Controller;
use LP_Statistics_DB;
use Throwable;

class BuilderDashboardAjax {
	use Singleton;

	public function init() {
		add_action( 'wp_ajax_lp_cb_dashboard_chart_data', [ $this, 'handle_chart_data' ] );
	}

	/**
	 * Handle AJAX request for chart data.
	 *
	 * @return void
	 */
	public function handle_chart_data() {
		try {
			if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'lp_cb_dashboard_nonce' ) ) {
				wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				wp_send_json_error( [ 'message' => 'Not logged in' ] );
			}

			$chart_type = sanitize_text_field( $_POST['chart_type'] ?? 'sales' );
			$period     = sanitize_text_field( $_POST['period'] ?? 'this_month' );
			$is_admin   = user_can( $user_id, 'administrator' );

			$instructor_id = $is_admin ? 0 : $user_id;

			$filter = $this->get_period_filter( $period );

			if ( empty( $filter ) ) {
				wp_send_json_error( [ 'message' => 'Invalid period' ] );
			}

			$lp_statistic_db = LP_Statistics_DB::getInstance();

			if ( $chart_type === 'sales' ) {
				$raw_data = $lp_statistic_db->get_net_sales_data_scoped(
					$filter['filter_type'],
					$filter['time'],
					$instructor_id
				);
			} else {
				$raw_data = $lp_statistic_db->get_enrollment_chart_data(
					$filter['filter_type'],
					$filter['time'],
					$instructor_id
				);
			}

			require_once LP_PLUGIN_PATH . 'inc/rest-api/v1/admin/class-lp-admin-rest-statistics-controller.php';
			$stats_controller = new LP_REST_Admin_Statistics_Controller();
			$chart_data       = $stats_controller->process_chart_data( $filter, $raw_data );

			wp_send_json_success( [
				'labels' => $chart_data['labels'] ?? [],
				'data'   => $chart_data['data'] ?? [],
			] );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => 'Server error' ] );
		}
	}

	/**
	 * Convert period string to filter params.
	 *
	 * @param string $period
	 * @return array
	 */
	private function get_period_filter( string $period ): array {
		switch ( $period ) {
			case 'this_week':
				return [
					'filter_type' => 'previous_days',
					'time'        => 6,
				];
			case 'this_month':
				return [
					'filter_type' => 'month',
					'time'        => current_time( 'Y-m-d' ),
				];
			case 'this_year':
				return [
					'filter_type' => 'year',
					'time'        => current_time( 'Y-m-d' ),
				];
			default:
				return [];
		}
	}
}
