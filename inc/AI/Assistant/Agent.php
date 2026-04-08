<?php

namespace LearnPress\AI\Assistant;

use Exception;
use LearnPress\Services\OpenAiService;

/**
 * AI Assistant Agent - handles the OpenAI function-calling loop.
 *
 * Orchestrates multi-turn conversation with tool calls:
 * 1. Sends messages + tool definitions to OpenAI.
 * 2. If model returns tool_calls, executes them via DataLoaders.
 * 3. Appends tool results and re-sends until model returns final content.
 * 4. Normalizes output into the assistant response contract.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.0
 */
class Agent {

	private const MAX_TOOL_ITERATIONS = 5;

	/**
	 * System prompt for the assistant model.
	 */
	private function get_system_prompt(): string {
		return __(
			'You are a helpful AI learning assistant for an online course. You help learners understand lesson content, explain concepts, generate practice quizzes, and review their quiz performance. Always ground your answers in the actual course data provided by tools. Respond in the same language the learner uses.',
			'learnpress'
		);
	}

	/**
	 * OpenAI tool definitions for function calling.
	 */
	private function get_tool_definitions(): array {
		return array(
			array(
				'type'     => 'function',
				'function' => array(
					'name'        => 'get_lesson_content',
					'description' => __( 'Retrieve the full content of a specific lesson.', 'learnpress' ),
					'parameters'  => array(
						'type'       => 'object',
						'properties' => array(
							'lesson_id' => array(
								'type'        => 'integer',
								'description' => __( 'The ID of the lesson to retrieve content for.', 'learnpress' ),
							),
						),
						'required'   => array( 'lesson_id' ),
					),
				),
			),
			array(
				'type'     => 'function',
				'function' => array(
					'name'        => 'get_course_outline',
					'description' => __( 'Retrieve the full course outline including all sections, lessons, and quizzes.', 'learnpress' ),
					'parameters'  => array(
						'type'       => 'object',
						'properties' => array(
							'course_id' => array(
								'type'        => 'integer',
								'description' => __( 'The ID of the course.', 'learnpress' ),
							),
						),
						'required'   => array( 'course_id' ),
					),
				),
			),
			array(
				'type'     => 'function',
				'function' => array(
					'name'        => 'get_quiz_results',
					'description' => __( 'Retrieve the quiz attempt results for a user in a course, including scores and weak areas.', 'learnpress' ),
					'parameters'  => array(
						'type'       => 'object',
						'properties' => array(
							'user_id'   => array(
								'type'        => 'integer',
								'description' => __( 'The ID of the user.', 'learnpress' ),
							),
							'course_id' => array(
								'type'        => 'integer',
								'description' => __( 'The ID of the course.', 'learnpress' ),
							),
						),
						'required'   => array( 'user_id', 'course_id' ),
					),
				),
			),
		);
	}

	/**
	 * Run the assistant agent loop.
	 *
	 * @param string $user_message  The learner's message.
	 * @param int    $lesson_id     Current lesson ID.
	 * @param int    $course_id     Current course ID.
	 * @param int    $user_id       Current user ID.
	 * @param array  $history       Previous conversation messages (role/content pairs).
	 * @param array  $active_quiz   Active quiz state for quiz-mode continuation.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 * @throws Exception
	 */
	public function run(
		string $user_message,
		int $lesson_id,
		int $course_id,
		int $user_id,
		array $history = array(),
		array $active_quiz = array()
	): array {
		$data_loaders = new DataLoaders();
		$service      = OpenAiService::instance();

		// Build the messages array.
		$messages   = array();
		$messages[] = array(
			'role'    => 'system',
			'content' => $this->get_system_prompt(),
		);

		// Append conversation history.
		foreach ( $history as $msg ) {
			if ( ! empty( $msg['role'] ) && isset( $msg['content'] ) ) {
				$messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}

		// Append current user message.
		$messages[] = array(
			'role'    => 'user',
			'content' => $user_message,
		);

		$tools = $this->get_tool_definitions();

		// Function-calling loop with iteration guard.
		for ( $i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++ ) {
			$response_message = $service->send_chat_request(
				array(
					'messages' => $messages,
					'tools'    => $tools,
				)
			);

			// If no tool_calls, the model produced a final text response.
			if ( empty( $response_message['tool_calls'] ) ) {
				return $this->build_response( $response_message['content'] ?? '' );
			}

			// Append assistant message with tool_calls to conversation.
			$messages[] = $response_message;

			// Execute each tool call and append results.
			foreach ( $response_message['tool_calls'] as $tool_call ) {
				$function_name = $tool_call['function']['name'] ?? '';
				$arguments     = json_decode( $tool_call['function']['arguments'] ?? '{}', true );
				$tool_call_id  = $tool_call['id'] ?? '';

				$result = $this->execute_tool( $data_loaders, $function_name, $arguments, $lesson_id, $course_id, $user_id );

				$messages[] = array(
					'role'         => 'tool',
					'tool_call_id' => $tool_call_id,
					'content'      => is_string( $result ) ? $result : wp_json_encode( $result ),
				);
			}
		}

		// Max iterations reached — return whatever content we have.
		return $this->build_response( __( 'I was unable to complete the request. Please try again.', 'learnpress' ) );
	}

	/**
	 * Execute a tool call and return the result.
	 *
	 * @param DataLoaders $loaders
	 * @param string      $function_name
	 * @param array       $arguments
	 * @param int         $lesson_id
	 * @param int         $course_id
	 * @param int         $user_id
	 *
	 * @return mixed
	 */
	private function execute_tool(
		DataLoaders $loaders,
		string $function_name,
		array $arguments,
		int $lesson_id,
		int $course_id,
		int $user_id
	) {
		switch ( $function_name ) {
			case 'get_lesson_content':
				$id = $arguments['lesson_id'] ?? $lesson_id;
				return $loaders->get_lesson_content( (int) $id, $user_id );

			case 'get_course_outline':
				$id = $arguments['course_id'] ?? $course_id;
				return $loaders->get_course_outline( (int) $id );

			case 'get_quiz_results':
				$uid = $arguments['user_id'] ?? $user_id;
				$cid = $arguments['course_id'] ?? $course_id;
				return $loaders->get_quiz_results( (int) $uid, (int) $cid );

			default:
				return array(
					'error' => sprintf(
						/* translators: %s: tool/function name requested by the model. */
						__( 'Unknown tool: %s', 'learnpress' ),
						$function_name
					),
				);
		}
	}

	/**
	 * Build normalized response from assistant content.
	 *
	 * @param string $content The raw assistant text content.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	private function build_response( string $content ): array {
		// Attempt to detect quiz JSON in the response.
		$decoded = json_decode( $content, true );

		if ( is_array( $decoded ) && ! empty( $decoded['questions'] ) ) {
			return array(
				'type'    => 'quiz',
				'message' => $decoded['intro'] ?? '',
				'quiz'    => array(
					'questions' => $decoded['questions'],
				),
			);
		}

		return array(
			'type'    => 'text',
			'message' => $content,
			'quiz'    => null,
		);
	}
}
