<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Helpers\OpenAi;
use LearnPress\Models\AiModel;
use LP_Settings;
use LP_Abstract_API;
/**
 * class LPAIAjax
 * Handle request Open AI
 *
 * @since 4.2.9.1
 * @version 1.0.0
 */
class LPAIAjax extends AbstractAjax {
	protected $user                = null;
	protected $text_model_type_url = 'https://api.openai.com/v1/chat/completions';
	protected $create_image_url    = 'https://api.openai.com/v1/images/generations';
	protected $edit_image_url      = 'https://api.openai.com/v1/images/edits';
	protected $secret_key;
	protected $text_model_type;
	protected $image_model_type;
	protected $frequency_penalty;
	protected $presence_penalty;
	protected $creativity_level;
	protected $max_token;

	public function __construct() {
		$lp_settings            = LP_Settings::instance();
		$this->secret_key       = $lp_settings->get( 'open_ai_secret_key' );
		$this->text_model_type  = $lp_settings->get( 'open_ai_text_model_type', 'chatgpt-4o-latest' );
		$this->image_model_type = $lp_settings->get( 'open_ai_image_model_type', 'dall-e-3' );

		$this->frequency_penalty = $lp_settings->get( 'open_ai_frequency_penalty_level', 0.0 );
		$this->presence_penalty  = $lp_settings->get( 'open_ai_presence_penalty_level', 0.0 );
		$this->creativity_level  = $lp_settings->get( 'open_ai_creativity_level', 1.0 );

		$this->max_token = $lp_settings->get( 'open_ai_max_token', 200 );
	}

	private function _check_permission() {
		if ( ! LP_Abstract_API::check_admin_permission() ) {
			$this->error( __( 'Permission denied', 'learnpress' ), 403 );
		}
	}

	/**
	 * Generic success response.
	 *
	 * @param mixed $data
	 * @param string $message
	 */
	private function success( $data = [], string $message = '' ) {
		wp_send_json_success(
			[
				'data'    => $data,
				'message' => $message,
			]
		);
	}

	/**
	 * Generic error response.
	 *
	 * @param string $message
	 * @param int $status_code
	 */
	private function error( string $message = '', int $status_code = 400 ) {
		wp_send_json_error(
			[
				'message' => $message,
				'status'  => $status_code,
			],
			$status_code
		);
	}

	public function generate_text() {
		$this->_check_permission();
		$params          = $_REQUEST;
		$prompt = $params['prompt'] ?? AiModel::get_completions_prompt($params)['prompt'];
		$data_response   = [ 'prompt' => $prompt ];

		$args = [
			'model'             => $this->text_model_type,
			'frequency_penalty' => floatval( $this->frequency_penalty ),
			'presence_penalty'  => floatval( $this->presence_penalty ),
			'n'                 => isset($params['outputs']) ? intval( $params['outputs'] ) : 1,
			'temperature'       => floatval( $this->creativity_level ),
		];

		if ( ! empty( $this->max_token ) ) {
			$args['max_tokens'] = intval( $this->max_token );
		}

		if ( in_array( $this->text_model_type, [ 'chatgpt-4o-latest', 'gpt-4o', 'gpt-4o-mini', 'gpt-4', 'gpt-3.5-turbo' ] ) ) {
			$this->text_model_type_url = 'https://api.openai.com/v1/chat/completions';
			$args['messages']          = [
				[
					'role'    => 'system',
					'content' => 'You are an AI assistant specialized in education and course design.',
				],
				[
					'role'    => 'user',
					'content' => $prompt,
				],
			];
		} elseif ( in_array( $this->text_model_type, [ 'gpt-3.5-turbo-instruct' ] ) ) {
			$this->text_model_type_url = 'https://api.openai.com/v1/completions';
			$args['prompt']            = $prompt;
		} else {
			$this->error( esc_html__( 'Invalid model', 'learnpress' ), 400 );
		}

		$response = wp_remote_post(
			$this->text_model_type_url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->secret_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => json_encode( $args ),
				'timeout' => 3600,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->error( $response->get_error_message(), 500 );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( isset( $result['error'] ) ) {
			$this->error( $result['error']['message'], $result['error']['code'] ?? 400 );
		}

		$content = [];
		if ( isset( $result['choices'] ) ) {
			foreach ( $result['choices'] as $choice ) {
				$text_content = $choice['message']['content'] ?? ( $choice['text'] ?? '' );
				$content[]    = $text_content;
			}
		}

		if ( ($params['type'] ?? '') === 'course-curriculum' ) {
			$aCurriculumCourse = [];
			$sections_data = [];
			foreach ( $content as $rawCurriculum ) {
				$curriculum = json_decode( $rawCurriculum, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array($curriculum) ) {
					$sections_data[] = $rawCurriculum; // Store original JSON
					$contentCurriculumCourse = '';
					foreach ( $curriculum['sections'] as $idx => $section ) {
						$sectionNumber             = $idx + 1;
						$contentCurriculumCourse .= "Section {$sectionNumber}: {$section['section_title']}\n";
						foreach ( $section['lessons'] as $lessonIdx => $lesson ) {
							$lessonNumber              = $lessonIdx + 1;
							$contentCurriculumCourse .= "Lesson {$sectionNumber}.{$lessonNumber}: {$lesson['lesson_title']}\n";
						}
					}
					$aCurriculumCourse[] = $contentCurriculumCourse;
				} else {

					$aCurriculumCourse[] = $rawCurriculum;
				}
			}
			$data_response['content']  = $aCurriculumCourse;
			$data_response['sections'] = $sections_data;
		} else {
			$data_response['content'] = $content;
		}

		$success_text = sprintf( __( 'Generate %s successfully!', 'learnpress' ), str_replace( '-', ' ', $params['type'] ) );
		$this->success( $data_response, $success_text );
	}

	public function create_course_feature_image() {
		$this->_check_permission();
		$params          = $_REQUEST;
		$data_response   = [];
		$generate_prompt = OpenAi::get_course_image_create_prompt( $params );
		$prompt          = $params['prompt'] ?? $generate_prompt;

		if ( empty( $params['prompt'] ) ) {
			$data_response['prompt'] = $generate_prompt;
		}

		if ( empty( $prompt ) ) {
			$this->error( __( 'Invalid prompt', 'learnpress' ), 400 );
		}

		$model   = LP_Settings::instance()->get( 'open_ai_image_model_type' );
		$outputs = $params['outputs'] ? intval( $params['outputs'] ) : 1;
		$size = $params['size'][0] ?? '1024x1024';

		$urls = $model == 'dall-e-3'
			? $this->generateWithDalle3( $this->secret_key, $prompt, $outputs, $size )
			: $this->generateWithDalle2( $this->secret_key, $prompt, $params['logoBase64'] ?? '', $params['maskBase64'] ?? '' );

		$data_response['urls'] = $urls;

		$this->success( $data_response, __( 'Generate course feature image successfully!', 'learnpress' ) );
	}

	public function save_feature_image() {
		$this->_check_permission();
		$params = $_REQUEST;

		if ( empty( $params['image_url'] ) ) {
			$this->error( __( 'Invalid image data', 'learnpress' ), 400 );
		}
		if ( empty( $params['post_id'] ) ) {
			$this->error( __( 'Invalid post id data', 'learnpress' ), 400 );
		}

		$attachment_id = $this->set_featured_image_from_url( $params['post_id'], $params['image_url'] );

		if ( is_wp_error( $attachment_id ) ) {
			$this->error( $attachment_id->get_error_message(), 500 );
		}

		if ( empty( $attachment_id ) ) {
			$this->error( __( 'Save failed', 'learnpress' ), 500 );
		}

		$this->success( [ 'id' => $attachment_id ], __( 'Save image successfully!', 'learnpress' ) );
	}


	private function set_featured_image_from_url( $post_id, $image_url ) {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$tmp = download_url( $image_url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = [
			'name'     => basename( $image_url ),
			'tmp_name' => $tmp,
		];

		$attachment_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return $attachment_id;
		}

		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	private function generateWithDalle3( $secret_key, $prompt, $n, $size = '1024x1024', $quality = 'standard', $style = 'vivid' ) {
		$args = [
			'model'           => 'dall-e-3',
			'prompt'          => $prompt,
			'n'               => $n,
			'size'            => $size,
			'quality'         => $quality,
			'style'           => $style,
			'response_format' => 'url',
		];

		$response = wp_remote_post('https://api.openai.com/v1/images/generations', [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => json_encode($args),
			'timeout' => 3600,
		]);

		if (is_wp_error($response)) {
			// Handle error
			return [];
		}

		$body   = wp_remote_retrieve_body($response);
		$result = json_decode($body, true);

		$urls = [];
		if (isset($result['data'])) {
			foreach ($result['data'] as $image_data) {
				$urls[] = $image_data['url'];
			}
		}

		return $urls;
	}

	private function generateWithDalle2( $secret_key, $prompt, $logoBase64, $maskBase64 ) {
		return []; // Placeholder
	}
}
