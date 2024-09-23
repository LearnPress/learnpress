<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Users_Controller
 *
 * @since 4.2.7
 */
class EditQuizApi extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin/edit';
		$this->rest_base = 'quiz';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'change-question-title' => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_question_title' ),
					'permission_callback' => '',
				),
			),
			'duplicate-question'    => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'duplicate_question' ),
					'permission_callback' => '',
				),
			),
			'remove-question'       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'remove_question' ),
					'permission_callback' => '',
				),
			),
			'delete-question'       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_question' ),
					'permission_callback' => '',
				),
			),
			'add-new-question'      => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_new_question' ),
					'permission_callback' => '',
				),
			),
			'sort-question'         => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'sort_question' ),
					'permission_callback' => '',
				),
			),
			'sort-question'         => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'sort_question' ),
					'permission_callback' => '',
				),
			),
			'search-question-items' => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'search_question_items' ),
					'permission_callback' => '',
				),
			),
			'add-questions-to-quiz' => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_questions_to_quiz' ),
					'permission_callback' => '',
				),
			),

		);

		parent::register_routes();
	}

	public function update_question_title( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$title       = $params['title'] ?? '';
		$question_id = $params['questionId'] ?? '';

		try {
			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Ids available!', 'learnpress' ) );
			}

			if ( empty( $title ) ) {
				throw new Exception( esc_html__( 'No title available!', 'learnpress' ) );
			}

			wp_update_post(
				array(
					'ID'         => $question_id,
					'post_title' => $title,
				)
			);
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function duplicate_question( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$quiz_id     = $params['quizId'] ?? '';
		$question_id = $params['questionId'] ?? '';

		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}
			$quiz_curd     = new LP_Quiz_CURD();
			$question_curd = new LP_Question_CURD();
			if ( ! class_exists( 'learn_press_duplicate_post_meta' ) ) {
				require_once LP_PLUGIN_PATH . '/inc/admin/lp-admin-functions.php';
			}
			$new_question_id = $question_curd->duplicate( $question_id, array( 'post_status' => 'publish' ) );
			$quiz_curd->add_question( $quiz_id, $new_question_id );
			$output = [
				'id'    => $new_question_id,
				'title' => get_the_title( $new_question_id ),
				'order' => -1,
			];

			ob_start();
			Template::instance()->get_admin_template(
				'quiz/question-item',
				$output
			);
			$response->data->html[] = ob_get_clean();

			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_new_question( WP_REST_Request $request ) {
		$response       = new LP_REST_Response();
		$params         = $request->get_params();
		$quiz_id        = $params['quizId'] ?? '';
		$question_title = $params['questionTitle'] ?? '';
		$question_type  = $params['questionType'] ?? '';
		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $question_title ) ) {
				throw new Exception( esc_html__( 'No Question Title available!', 'learnpress' ) );
			}

			if ( empty( $question_type ) ) {
				throw new Exception( esc_html__( 'No Question Type available!', 'learnpress' ) );
			}

			$question_curd = new LP_Question_CURD();
			$args          = array(
				'quiz_id' => $quiz_id,
				'title'   => $question_title,
				'type'    => $question_type,
			);
			$new_question  = $question_curd->create( $args );
			$question_id   = $new_question->get_id();

			$output = [
				'id'    => $question_id,
				'title' => $question_title,
				'order' => -1,
			];

			ob_start();
			Template::instance()->get_admin_template(
				'quiz/question-item',
				$output
			);
			$response->data->html[] = ob_get_clean();
			$response->status       = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function remove_question( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$quiz_id     = $params['quizId'] ?? '';
		$question_id = $params['questionId'] ?? '';

		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			$quiz_curd = new LP_Quiz_CURD();
			$quiz_curd->remove_questions( $quiz_id, $question_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function delete_question( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$quiz_id     = $params['quizId'] ?? '';
		$question_id = $params['questionId'] ?? '';

		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $question_id ) ) {
				throw new Exception( esc_html__( 'No Question Id available!', 'learnpress' ) );
			}

			wp_trash_post( $question_id );
			$lp_quiz_cache = LP_Quiz_Cache::instance();
			$key_cache     = "$quiz_id/question_ids";
			$lp_quiz_cache->clear( $key_cache );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function sort_question( WP_REST_Request $request ) {
		$response     = new LP_REST_Response();
		$params       = $request->get_params();
		$quiz_id      = $params['quizId'] ?? '';
		$question_ids = $params['questionIds'] ?? '';

		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $question_ids ) ) {
				throw new Exception( esc_html__( 'No Question Ids available!', 'learnpress' ) );
			}

			$quiz_curd = new LP_Quiz_CURD();
			$quiz_curd->sort_questions( $question_ids );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function search_question_items( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$params   = $request->get_params();
		$quiz_id  = $params['quizId'] ?? '';
		$query    = $params['query'] ?? '';
		$page     = $params['page'] ?? 1;
		$exclude  = $params['exclude'] ?? '';
		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( ! class_exists( 'LP_Modal_Search_Items' ) ) {
				require_once LP_PLUGIN_PATH . '/inc/admin/class-lp-modal-search-items.php';
			}

			$ids_exclude = array();
			if ( is_array( $exclude ) ) {
				foreach ( $exclude as $item ) {
					$ids_exclude[] = $item['id'];
				}
			}

			$search = new LP_Modal_Search_Items(
				array(
					'type'       => 'lp_question',
					'context'    => 'quiz',
					'context_id' => $quiz_id,
					'term'       => $query,
					'limit'      => apply_filters( 'learn-press/quiz-editor/choose-items-limit', 10 ),
					'paged'      => $page,
					'exclude'    => $ids_exclude,
				)
			);

			$html_items                       = $search->get_html_items();
			$pagination                       = $search->get_pagination( false );
			$response->data->html->items      = $html_items ?? '';
			$response->data->html->pagination = $pagination ?? '';
			$response->status                 = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_questions_to_quiz( WP_REST_Request $request ) {
		$response  = new LP_REST_Response();
		$params    = $request->get_params();
		$quiz_id   = $params['quizId'] ?? '';
		$questions = $params['items'] ?? '';
		try {
			if ( empty( $quiz_id ) ) {
				throw new Exception( esc_html__( 'No Quiz Id available!', 'learnpress' ) );
			}

			if ( empty( $questions ) ) {
				throw new Exception( esc_html__( 'No Question available!', 'learnpress' ) );
			}

			$quiz_curd = new LP_Quiz_CURD();

			foreach ( $questions as $key => $question ) {
				$quiz_curd->add_question( $quiz_id, $question['id'] );
				$output = [
					'id'    => $question['id'],
					'title' => $question['title'],
					'order' => -1,
				];

				ob_start();
				Template::instance()->get_admin_template(
					'quiz/question-item',
					$output
				);
				$response->data->html[] = ob_get_clean();
			}
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
