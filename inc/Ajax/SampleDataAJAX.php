<?php
/**
 * Class SampleDataAJAX
 *
 * Handle install/uninstall sample data via LearnPress AJAX dispatcher.
 *
 * @since 4.4.5
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\SampleDataService;
use LP_Helper;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SampleDataAJAX
 */
class SampleDataAJAX extends AbstractAjax {
	/**
	 * Install sample course data via AJAX.
	 *
	 * @return void
	 */
	public function lp_install_sample_data() {
		$response = new Response();
		ini_set( 'max_execution_time', 0 );

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					esc_html__(
						'You do not have permission to install sample data.',
						'learnpress'
					)
				);
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( esc_html__( 'Invalid request.', 'learnpress' ) );
			}

			$service = SampleDataService::instance();

			// Convert string to array.
			$items_range = [
				'section_range',
				'item_range',
				'question_range',
				'answer_range',
			];

			foreach ( $items_range as $item_range ) {
				$params[ $item_range ] = $params[ $item_range ] ?? '';
				if ( ! empty( $params[ $item_range ] ) ) {
					$params[ $item_range ] = explode( ',', $params[ $item_range ] );
				}
			}

			$coursePostModel = $service->install( $params );

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = sprintf(
				__( 'The Course "%s" has been created.', 'learnpress' ),
				$coursePostModel->get_the_title()
			);

			$message_html         = sprintf(
				__( 'The course "%1$s" has been created %2$s | %3$s', 'learnpress' ),
				$coursePostModel->get_the_title(),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$coursePostModel->get_permalink(),
					__( 'View', 'learnpress' )
				),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$coursePostModel->get_edit_link(),
					__( 'Edit', 'learnpress' )
				),
			);
			$response->data->html = Template::print_message(
				$message_html,
				Response::STATUS_SUCCESS,
				false
			);
		} catch ( Throwable $ex ) {
			$response->message    = $ex->getMessage();
			$response->data->html = Template::print_message(
				__( 'The course data created failed!', 'learnpress' ),
				Response::STATUS_ERROR,
				false
			);
		}

		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}

	/**
	 * Uninstall sample data via AJAX.
	 *
	 * @return void
	 */
	public function lp_uninstall_sample_data() {
		$response = new Response();

		try {
			set_time_limit( 0 );

			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( esc_html__( 'You do not have permission to uninstall sample data.', 'learnpress' ) );
			}

			SampleDataService::instance()->uninstall();

			$response->status     = Response::STATUS_SUCCESS;
			$response->message    = __( 'The sample data was successfully deleted!', 'learnpress' );
			$response->data->html = Template::print_message(
				__( 'The sample data was successfully deleted!', 'learnpress' ),
				Response::STATUS_SUCCESS,
				false
			);
		} catch ( Throwable $ex ) {
			$response->message    = $ex->getMessage();
			$response->data->html = Template::print_message(
				__( 'Sample data uninstall failed!', 'learnpress' ),
				Response::STATUS_ERROR,
				false
			);
		}

		set_time_limit( LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}
}
