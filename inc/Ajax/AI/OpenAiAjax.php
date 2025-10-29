<?php

namespace LearnPress\Ajax\AI;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use LearnPress\Services\OpenAiService;
use LearnPress\TemplateHooks\Admin\AdminCreateCourseAITemplate;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * class OpenAiAjax
 * Handle request Open AI
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class OpenAiAjax extends AbstractAjax {
	/**
	 * Generate prompt course with AI
	 */
	public function openai_generate_prompt_course() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				return;
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = Config::instance()->get( 'prompt-create-course', 'settings/openAi', compact( 'params' ) );

			$response->data    = $prompt;
			$response->status  = 'success';
			$response->message = __( 'Generate prompt successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate data course with prompt submitted
	 */
	public function openai_generate_data_course() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				return;
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = $params['lp-openai-prompt-generated-field'] ?? '';

			$args   = [
				'prompt' => $prompt,
			];
			$result = OpenAiService::instance()->send_request( $args );

			$result['html_preview'] = AdminCreateCourseAITemplate::html_preview_with_data( $result );

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate prompt successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
