<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Helpers\Singleton;
use LP_Helper;
use LP_Settings;

/**
 * Class OpenAiService
 *
 * Handles interactions with the OpenAI API for LearnPress.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.0
 */
class OpenAiService {
	use Singleton;

	public string $baseUrl = 'https://api.openai.com/v1/';
	public string $urlChartCompletion;
	public string $urlResponses;
	public string $urlCompletionLegacy;

	public string $secret_key;
	public string $text_model_type;
	public string $image_model_type;
	public float $frequency_penalty;
	public float $presence_penalty;
	public float $creativity_level;
	public int $max_token;

	public function init() {
		$this->urlChartCompletion  = $this->baseUrl . 'chat/completions';
		$this->urlCompletionLegacy = $this->baseUrl . 'completion';
		$this->urlResponses        = $this->baseUrl . 'responses';
		$this->get_settings();
	}

	/**
	 * Check OpenAI integration is enabled
	 */
	public function is_enable(): bool {
		return LP_Settings::get_option( 'enable_open_ai', 'no' ) === 'yes';
	}

	public function get_settings() {
		$this->secret_key        = LP_Settings::get_option( 'open_ai_secret_key', '' );
		$this->text_model_type   = LP_Settings::get_option( 'open_ai_text_model_type', 'chatgpt-4o-latest' );
		$this->image_model_type  = LP_Settings::get_option( 'open_ai_image_model_type', 'dall-e-3' );
		$this->frequency_penalty = LP_Settings::get_option( 'open_ai_frequency_penalty_level', 0.0 );
		$this->presence_penalty  = LP_Settings::get_option( 'open_ai_presence_penalty_level', 0.0 );
		$this->creativity_level  = LP_Settings::get_option( 'open_ai_creativity_level', 1.0 );
		$this->max_token         = LP_Settings::get_option( 'open_ai_max_token', 200 );
	}

	/**
	 * Call OpenAI API
	 *
	 * @throws Exception
	 */
	public function send_request( array $args ) {
		// Handle args before send request
		$url = '';

		if ( in_array(
			$this->text_model_type,
			[ 'chatgpt-4o-latest', 'gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo' ]
		) ) {
			$url  = $this->urlChartCompletion;
			$args = $this->handle_params_for_send_chat_completion( $args );
		} elseif ( $this->text_model_type == 'gpt-3.5-turbo-instruct' ) {
			$url  = $this->urlCompletionLegacy;
			$args = $this->handle_params_for_send_completion_legacy( $args );
		} else {
			$url  = $this->urlResponses;
			$args = $this->handle_params_for_send_responses( $args );
		}

		$response = wp_remote_post(
			$url,
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
			throw new Exception( $response->get_error_message(), 400 );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = LP_Helper::json_decode( $body, true );
		if ( isset( $result['error'] ) ) {
			throw new Exception( $result['error']['message'] );
		}

		return $this->detected_data( $data );
	}

	/**
	 * Handle params for send chat completion
	 *
	 * @docs https://platform.openai.com/docs/api-reference/chat/create
	 *
	 * @throws Exception
	 */
	public function handle_params_for_send_chat_completion( $params ): array {
		return [
			'model'             => $this->text_model_type,
			'frequency_penalty' => $this->frequency_penalty,
			'presence_penalty'  => $this->presence_penalty,
			'temperature'       => $this->creativity_level,
			'response_format'   => [ 'type' => 'json_object' ],
			'n'                 => $args['outputs'] ?? 1,
			'messages'          => [
				[
					'role'    => 'system',
					'content' => 'You are an AI assistant specialized in education and course design.',
				],
				[
					'role'    => 'user',
					'content' => $params['prompt'] ?? '',
				],
			],
		];
	}

	/**
	 * Handle params for send chat completion
	 *
	 * @docs https://platform.openai.com/docs/api-reference/completions/create
	 *
	 * @throws Exception
	 */
	public function handle_params_for_send_completion_legacy( $params ): array {
		return [
			'model'             => $this->text_model_type,
			'temperature'       => $this->creativity_level,
			'max_output_tokens' => $this->max_token,
			'n'                 => $args['outputs'] ?? 1,
			'prompt'            => $params['prompt'] ?? '',
		];
	}

	/**
	 * Handle params for send chat completion
	 *
	 * @docs https://platform.openai.com/docs/api-reference/responses/create
	 *
	 * @throws Exception
	 */
	public function handle_params_for_send_responses( $params ): array {
		return [
			'model'             => $this->text_model_type,
			'temperature'       => $this->creativity_level,
			'max_output_tokens' => $this->max_token,
			'input'             => $params['prompt'] ?? '',
		];
	}

	/**
	 * Detect data from response
	 *
	 * @throws Exception
	 */
	public function detected_data( array $data ): array {
		$data['structure_data'] = [];

		if ( isset( $data['choices'] ) && is_array( $data['choices'] ) ) {
			$results = [];
			foreach ( $data['choices'] as $choice ) {
				if ( isset( $choice['message']['content'] ) ) {
					$results[] = LP_Helper::json_decode( $choice['message']['content'], true );
				} elseif ( isset( $choice['text'] ) ) {
					$results[] = LP_Helper::json_decode( $choice['text'], true );
				}
			}
			return $results;
		} elseif ( isset( $data['output'] ) ) {
			foreach ( $data['output'] as $output ) {
				foreach ( $output['content'] as $content ) {
					$data['structure_data'][] = LP_Helper::json_decode( $content['text'] ?? '', true );
				}
			}
		}

		return $data;
	}
}
