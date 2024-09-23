<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Users_Controller
 *
 * @since 4.2.7
 */
class EditQuestionApi extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin/edit';
		$this->rest_base = 'question';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'html-question/(?P<question_id>[\d]+)' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'html_question' ),
					'permission_callback' => '',
				),
			),
			'change-question-type'                 => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'change_question_type' ),
					'permission_callback' => '',
				),
			),
			'update-answer-title'                  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_answer_title' ),
					'permission_callback' => '',
				),
			),
			'add-new-answer'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_new_answer' ),
					'permission_callback' => '',
				),
			),
			'delete-answer'                        => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_answer' ),
					'permission_callback' => '',
				),
			),
			'change-correct'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'change_correct' ),
					'permission_callback' => '',
				),
			),
			'sort-answer'                          => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'sort_answer' ),
					'permission_callback' => '',
				),
			),

		);

		parent::register_routes();
	}

	public function html_question( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$question_id          = $params['question_id'] ?? 0;

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( get_post_type( $question_id ) !== LP_QUESTION_CPT ) {
				throw new Exception( esc_html__( 'Post is not a question post type!', 'learnpress' ) );
			}

			$question = LP_Question::get_question( $question_id );

			if ( empty( $question ) ) {
				throw new Exception( esc_html__( 'No question found!', 'learnpress' ) );
			}

			$question_data = array(
				'id'      => $question_id,
				'open'    => false,
				'title'   => get_the_title( $question_id ),
				'type'    => array(
					'key'   => $question->get_type(),
					'label' => $question->get_type_label(),
				),
				'answers' => is_array( $question->get_data( 'answer_options' ) ) ? array_values( $question->get_data( 'answer_options' ) ) : array(),
			);

			$get_data_answer = $this->get_data_answer();

			ob_start();
			Template::instance()->get_admin_template(
				$get_data_answer[ $question_data['type']['key'] ],
				$question_data
			);
			$response->data->html[] = ob_get_clean();
			$response->status       = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function change_question_type( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$question_id          = $params['questionId'] ?? 0;
		$type                 = $params['type'] ?? 0;

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( empty( $type ) ) {
				throw new Exception( esc_html__( 'No Question Type available!', 'learnpress' ) );
			}

			$question      = LP_Question::get_question( $question_id );
			$question_curd = new LP_Question_CURD();

			if ( empty( $question ) ) {
				throw new Exception( esc_html__( 'No Question Found!', 'learnpress' ) );
			}

			$question_curd->change_question_type( $question, $type );
			$data        = array(
				'id'      => $question_id,
				'open'    => false,
				'title'   => get_the_title( $question_id ),
				'type'    => array(
					'key'   => $question->get_type(),
					'label' => $question->get_type_label(),
				),
				'answers' => is_array( $question->get_data( 'answer_options' ) ) ? array_values( $question->get_data( 'answer_options' ) ) : array(),
			);
			$data_answer = $this->get_data_answer();
			ob_start();
			Template::instance()->get_admin_template(
				$data_answer[ $type ],
				$data
			);
			$response->data->html[] = ob_get_clean();
			$response->status       = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function get_data_answer() {
		$data_answer = [
			'true_or_false'  => 'question/answer-refactor',
			'multi_choice'   => 'question/answer-refactor',
			'single_choice'  => 'question/answer-refactor',
			'fill_in_blanks' => 'question/fib-answer-editor-refactor',
		];

		apply_filters( 'learnpress/question/get_data_answer', $data_answer );
		return $data_answer;
	}

	public function update_answer_title( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$answer               = $params['answer'] ?? '';
		$question_id          = $params['questionId'] ?? '';
		$answer               = is_string( $answer ) ? json_decode( wp_unslash( $answer ), true ) : $answer;

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( empty( $answer ) ) {
				throw new Exception( esc_html__( 'No Answer available!', 'learnpress' ) );
			}

			$question_curd = new LP_Question_CURD();
			$question_curd->update_answer_title( $question_id, $answer );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_new_answer( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$question_id          = $params['questionId'] ?? 0;

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			$answer        = LP_Question::get_default_answer();
			$question_curd = new LP_Question_CURD();
			$question_curd->new_answer( $question_id, $answer );
			$question = LP_Question::get_question( $question_id );
			if ( empty( $question ) ) {
				throw new Exception( esc_html__( 'No Question Found!', 'learnpress' ) );
			}

			$answers     = is_array( $question->get_data( 'answer_options' ) ) ? array_values( $question->get_data( 'answer_options' ) ) : array();
			$data        = array(
				'id'      => $question_id,
				'open'    => false,
				'title'   => get_the_title( $question_id ),
				'type'    => array(
					'key'   => $question->get_type(),
					'label' => $question->get_type_label(),
				),
				'answers' => $answers,
			);
			$data_answer = $this->get_data_answer();

			ob_start();
			Template::instance()->get_admin_template(
				$data_answer[ $question->get_type() ],
				$data
			);
			$response->data->html[] = ob_get_clean();
			$response->status       = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function delete_answer( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$answer_id            = $params['answerId'] ?? 0;
		$question_id          = $params['questionId'] ?? 0;

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( empty( $answer_id ) ) {
				throw new Exception( esc_html__( 'No Answer Id available!', 'learnpress' ) );
			}

			$question_curd = new LP_Question_CURD();
			$question_curd->delete_answer( $question_id, $answer_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function change_correct( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$answer               = $params['answer'] ?? '';
		$question_id          = $params['questionId'] ?? '';

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( empty( $answer ) ) {
				throw new Exception( esc_html__( 'No Answer available!', 'learnpress' ) );
			}

			$question = LP_Question::get_question( $question_id );
			if ( empty( $question ) ) {
				throw new Exception( esc_html__( 'No Question Found!', 'learnpress' ) );
			}

			$question_curd = new LP_Question_CURD();
			$question_curd->change_correct_answer( $question, $answer );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function sort_answer( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$answer_ids           = $params['answerIds'] ?? '';
		$question_id          = $params['questionId'] ?? '';

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			if ( empty( $answer_ids ) ) {
				throw new Exception( esc_html__( 'No Answer Ids available!', 'learnpress' ) );
			}

			$question_curd = new LP_Question_CURD();
			$question_curd->sort_answers( $question_id, $answer_ids );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
