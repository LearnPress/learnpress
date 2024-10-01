<?php

use LearnPress\Helpers\OpenAi;
use LearnPress\Models\CourseModel;

class LP_REST_Admin_OpenAI_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;
	protected $text_model_type_url = 'https://api.openai.com/v1/chat/completions';
	protected $create_image_url = 'https://api.openai.com/v1/images/generations';
	protected $edit_image_url = 'https://api.openai.com/v1/images/edits';
	protected $secret_key;
	protected $text_model_type;
	protected $image_model_type;

	protected $frequency_penalty;
	protected $presence_penalty;
	protected $creativity_level;

	protected $max_token;


	public function __construct() {

		$this->namespace = 'lp/v1';
		$this->rest_base = 'open-ai';

		$lp_settings            = LP_Settings::instance();
		$this->secret_key       = $lp_settings->get( 'open_ai_secret_key' );
		$this->text_model_type  = $lp_settings->get( 'open_ai_text_model_type', 'chatgpt-4o-latest' );
		$this->image_model_type = $lp_settings->get( 'open_ai_image_model_type', 'dall-e-3' );

		$this->frequency_penalty = $lp_settings->get( 'open_ai_frequency_penalty_level', 0.0 );
		$this->presence_penalty  = $lp_settings->get( 'open_ai_presence_penalty_level', 0.0 );
		$this->creativity_level  = $lp_settings->get( 'open_ai_creativity_level', 1.0 );

		$this->max_token = $lp_settings->get( 'open_ai_max_token', 200 );

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'generate-text'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_text' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'create-feature-image' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_course_feature_image' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'edit-feature-image'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'edit_course_feature_image' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'save-feature-image'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_image' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'apply-section'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_section' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'add-question'         => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_question' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'curriculum-quiz'      => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'curriculum_quiz' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * @return bool
	 */
	public function check_admin_permission() {
		return LP_Abstract_API::check_admin_permission();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function curriculum_quiz( WP_REST_Request $request ) {
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );
			$params = $request->get_params();

			$course_id     = $params['course_id'] ?? '';
			$section_id    = $params['section_id'] ?? '';
			$section_order = $params['section_order'] ?? '';
			$quiz_title    = $params['title'] ?? '';
			$quiz_title    = trim( $quiz_title );
			$course        = CourseModel::find( $course_id, true );

			if ( empty( $course_id ) ) {
				return $this->error( __( 'Course id is required', 'learnpress' ), 400 );
			}


			$filters             = new LP_Section_Items_Filter();
			$filters->section_id = $section_id;
			$query_results       = LP_Section_DB::getInstance()->get_section_items_by_section_id( $filters );
			$total               = $query_results['results']['total'] ?? 0;

			$quiz_args = array(
				'post_type'   => LP_QUIZ_CPT,
				'post_status' => 'publish',
				'post_title'  => $quiz_title
			);
			require_once( ABSPATH . 'wp-admin/includes/post.php' );

			$existing_quizzes = post_exists( $quiz_title, '', '', LP_QUIZ_CPT, 'publish' );

			$quiz_obj = new StdClass();
			if ( $existing_quizzes ) {
				$quiz_id = $existing_quizzes;
				$quiz    = [
					'id'      => $existing_quizzes,
					'order'   => $total + 1,
					'type'    => LP_QUIZ_CPT,
					'title'   => $quiz_title,
					'preview' => ''
				];
			} else {
				$quiz_args['post_content'] = '';

				$quiz_id = wp_insert_post( $quiz_args );

				if ( empty( $quiz_id ) ) {
					return $this->error( __( 'Can not insert quiz', 'learnpress' ), 400 );
				}

				if ( is_wp_error( $quiz_id ) ) {
					return $this->error( $quiz_id->get_error_message(), $quiz_id->get_error_code() );
				}

				$quiz = [
					'id'      => $quiz_id,
					'order'   => $total + 1,
					'type'    => LP_QUIZ_CPT,
					'title'   => $quiz_title,
					'preview' => ''
				];
			}

			foreach ( $quiz as $quiz_key => $value ) {
				$quiz_obj->$quiz_key = $value;
			}

			include_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin.php';
			learn_press_course_insert_section_item(
				array(
					'section_id' => $section_id,
					'item_id'    => $quiz_id,
					'item_order' => $total + 1,
					'item_type'  => LP_QUIZ_CPT,
				)
			);

			$course->sections_items[ $section_order ]->items[] = $quiz_obj;

			//Total items
			$total_item              = $course->total_items;
			$total_item->count_items = $total_item->count_items + 1;
			$total_item->lp_quiz     = $total_item->lp_quiz + 1;

			$course->total_items = $total_item;

			$course->save();


			$wpdb->query( 'COMMIT' );

			return $this->success( esc_html__( 'Apply quiz successfully!', 'learnpress' ) );
		} catch ( Exception $error ) {
			$wpdb->query( 'ROLLBACK' );

			return $this->error( $error->getMessage(), $error->getCode() );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function add_question( WP_REST_Request $request ) {
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );
			$params = $request->get_params();

			$quiz_id = $params['quiz_id'] ?? '';

			if ( empty( $quiz_id ) ) {
				return $this->error( __( 'Quiz id is required', 'learnpress' ), 400 );
			}

			$questions = $params['questions'] ?? array();
			foreach ( $questions as $question ) {

				$question_type = $question['question_type'];

				$title = $question['question_title'];

				require_once( ABSPATH . 'wp-admin/includes/post.php' );
				$existing_questions = post_exists( $title, '', '', LP_QUESTION_CPT, 'publish' );

				if ( $existing_questions ) {
					$question_id = $existing_questions;
				} else {
					$question_id = wp_insert_post( array(
						'post_title'   => $title,
						'post_content' => '',
						'post_type'    => LP_QUESTION_CPT,
						'post_status'  => 'publish',
					) );

					if ( empty( $question_id ) ) {
						return $this->error( __( 'Can not insert question', 'learnpress' ), 400 );
					}

					if ( is_wp_error( $question_id ) ) {
						return $this->error( $question_id->get_error_message(), $question_id->get_error_code() );
					}

					update_post_meta( $question_id, '_lp_type', $question_type );

					if ( isset( $question['points'] ) ) {
						update_post_meta( $question_id, '_lp_mark', $question['points'] );
					}
					if ( isset( $question['hint'] ) ) {
						update_post_meta( $question_id, '_lp_hint', $question['hint'] );
					}
					if ( isset( $question['explanation'] ) ) {
						update_post_meta( $question_id, '_lp_explanation', $question['explanation'] );
					}

					if ( $question_type === 'fill_in_blanks' ) {
						$title  = $question['question_content'];
						$answer = $question['answer'];

						$parts = explode( '___', $title );

						$title_result = $parts[0];
						$answer_count = count( $answer );
						$blanks       = array();
						for ( $i = 0; $i < $answer_count; $i ++ ) {
							$unique_id    = learn_press_random_value();
							$title_result .= '[fib fill="' . $answer[ $i ] . '" id="' . $unique_id . '"]';
							if ( isset( $parts[ $i + 1 ] ) ) {
								$title_result .= $parts[ $i + 1 ];
							}

							$blanks[ $unique_id ] = array(
								'fill'       => $answer[ $i ],
								'id'         => $unique_id,
								'comparison' => '',
								'match_case' => 0,
								'index'      => $i + 1,
								'open'       => ''
							);
						}

						$id = $wpdb->insert(
							$wpdb->learnpress_question_answers,
							array(
								'question_id' => $question_id,
								'title'       => $title_result,
								'is_true'     => 'yes',
								'order'       => 1,
							),
							array(
								'%d',
								'%s',
								'%s',
								'%d'
							)
						);

						$wpdb->insert(
							$wpdb->learnpress_question_answermeta,
							array(
								'learnpress_question_answer_id' => $id,
								'meta_key'   => '_blanks',
								'meta_value' => serialize($blanks),
							),
							array( '%d', '%s', '%s' )
						);
					} else {
						$options = $question['options'];
						$answer  = $question['answer'];
						foreach ( $options as $order => $option ) {
							$is_true = '';
							if ( is_array( $answer ) && in_array( $option, $answer ) ) {
								$is_true = 'yes';
							}

							if ( is_string( $answer ) && $option === $answer ) {
								$is_true = 'yes';
							}
							$wpdb->insert(
								$wpdb->learnpress_question_answers,
								array(
									'question_id' => $question_id,
									'title'       => $option,
									'value'       => learn_press_random_value(),
									'is_true'     => $is_true,
									'order'       => $order,
								),
								array(
									'%d',
									'%s',
									'%s',
									'%s',
									'%s',
								)
							);
						}
					}
				}

				$query = $wpdb->prepare(
					"
				SELECT max(question_order)
				FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
				",
					$quiz_id
				);

				$order = $wpdb->get_var( $query );

				if ( $order ) {
					$order ++;
				} else {
					$order = 1;
				}

				$inserted = $wpdb->insert(
					$wpdb->prefix . 'learnpress_quiz_questions',
					array(
						'quiz_id'        => $quiz_id,
						'question_id'    => $question_id,
						'question_order' => $order,
					),
					array( '%d', '%d', '%d' )
				);
			}

			$wpdb->query( 'COMMIT' );

			return $this->success( esc_html__( 'Add question successfully!', 'learnpress' ) );
		} catch ( Exception $error ) {
			$wpdb->query( 'ROLLBACK' );

			return $this->error( $error->getMessage(), $error->getCode() );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function apply_section( WP_REST_Request $request ) {
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );
			$params = $request->get_params();

			$course_id = $params['course_id'] ?? '';

			if ( empty( $course_id ) ) {
				return $this->error( __( 'Course id is required', 'learnpress' ), 400 );
			}

			$sections = $params['sections'] ?? array();

			foreach ( $sections as $section ) {
				$section_title = $section['section_title'] ?? '';
				if ( empty( $section_title ) ) {
					return $this->error( __( 'Section title is required', 'learnpress' ), 400 );
				}

				$lessons     = $section['lessons'] ?? array();
				$lesson_args = array(
					'post_type'   => LP_LESSON_CPT,
					'post_status' => 'publish',
				);

				$lesson_data = array();
				$course      = CourseModel::find( $course_id, true );

				foreach ( $lessons as $key => $lesson_title ) {
					$lesson_args['post_title'] = $lesson_title;
					require_once( ABSPATH . 'wp-admin/includes/post.php' );
					$existing_lessons = post_exists( $lesson_title, '', '', LP_LESSON_CPT, 'publish' );

					$lesson_obj = new StdClass();
					if ( $existing_lessons ) {
						$lesson = [
							'id'      => $existing_lessons,
							'order'   => $key,
							'type'    => LP_LESSON_CPT,
							'title'   => $lesson_title,
							'preview' => ''
						];
					} else {
						$lesson_args['post_content'] = '';
						$lesson_id                   = wp_insert_post( $lesson_args );

						if ( empty( $lesson_id ) ) {
							return $this->error( __( 'Can not insert lesson', 'learnpress' ), 400 );
						}

						if ( is_wp_error( $lesson_id ) ) {
							return $this->error( $lesson_id->get_error_message(), $lesson_id->get_error_code() );
						}

						$lesson = [
							'id'      => $lesson_id,
							'order'   => $key,
							'type'    => LP_LESSON_CPT,
							'title'   => $lesson_title,
							'preview' => ''
						];
					}

					foreach ( $lesson as $lesson_key => $value ) {
						$lesson_obj->$lesson_key = $value;
					}

					$lesson_data[] = $lesson_obj;
				}


				$sections_items = array();
				if ( $course->sections_items ) {
					$sections_items = $course->sections_items;
				}

				$total_section_items             = count( $sections_items );
				$item_order                      = $total_section_items + 1;
				$new_section_item                = new StdClass();
				$new_section_item->id            = $item_order;
				$new_section_item->section_id    = $item_order;
				$new_section_item->order         = $item_order;
				$new_section_item->section_order = $item_order;
				$new_section_item->title         = $section_title;
				$new_section_item->section_name  = $section_title;
				$new_section_item->description   = '';
				$new_section_item->items         = $lesson_data;

				//Section items
				$course->sections_items[] = $new_section_item;

				//Total items
				$total_item = $course->total_items;

				$total_item->count_items = $total_item->count_items + count( $lesson_data );
				$total_item->lp_lesson   = $total_item->lp_lesson + count( $lesson_data );

				$course->total_items = $total_item;

				$course->save();
				include_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin.php';
				$section_id = learn_press_course_insert_section( array(
						'section_name'        => $section_title,
						'section_course_id'   => $course_id,
						'section_order'       => $item_order,
						'section_description' => '',
					)
				);

				if ( empty( $section_id ) ) {
					throw new Exception( __( 'Can not insert section', 'learnpress' ) );
				}

				foreach ( $lesson_data as $lesson_data_item ) {
					learn_press_course_insert_section_item(
						array(
							'section_id' => $section_id,
							'item_id'    => $lesson_data_item->id,
							'item_order' => $lesson_data_item->order,
							'item_type'  => LP_LESSON_CPT,
						)
					);
				}
			}

			$wpdb->query( 'COMMIT' );

			return $this->success( esc_html__( 'Apply section successfully!', 'learnpress' ) );
		} catch ( Exception $error ) {
			$wpdb->query( 'ROLLBACK' );

			return $this->error( $error->getMessage(), $error->getCode() );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function save_image( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( empty( $params['image_data'] ) ) {
			return $this->error( __( 'Invalid image data', 'learnpress' ), 400 );
		}

		$attachment_id = $this->upload_base64_image_to_media_library( $params['image_data'] );

		if ( is_wp_error( $attachment_id ) ) {
			return $this->error( __( $attachment_id->get_error_message() ), $attachment_id->get_error_code() );
		}

		if ( empty( $attachment_id ) ) {
			return $this->error( __( 'Save failed', 'learnpress' ), 400 );
		}

		$data['id'] = $attachment_id;

		return $this->success( esc_html__( 'Save image successfully!', 'learnpress' ), $data );
	}


	/**
	 * @param $base64_image
	 *
	 * @return int|WP_Error
	 */
	public function upload_base64_image_to_media_library( $base64_image ) {
		$parts = explode( ';', $base64_image );
		if ( count( $parts ) < 2 ) {
			return new WP_Error( 'invalid_image_format', __( 'Invalid image format', 'learnpress' ) );
		}

		$type = str_replace( 'data:', '', $parts[0] );
		$data = $parts[1];
		$data = explode( ',', $data )[1] ?? '';
		$data = base64_decode( $data, true );
		if ( $data === false ) {
			return new WP_Error( 'base64_decode_error', __( 'Failed to decode base64 data', 'learnpress' ) );
		}

		$mime_types = array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp'
		);

		if ( ! array_key_exists( $type, $mime_types ) ) {
			return new WP_Error( 'invalid_image_type', __( 'Invalid image type', 'learnpress' ) );
		}

		$extension  = $mime_types[ $type ];
		$image_name = 'image_' . time() . '.' . $extension;

		$temp_file = tempnam( sys_get_temp_dir(), 'wp_image_' );
		if ( file_put_contents( $temp_file, $data ) === false ) {
			return new WP_Error( 'file_write_error', __( 'Failed to write to temporary file', 'learnpress' ) );
		}

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();

		global $wp_filesystem;

		$upload_dir = wp_upload_dir();
		$file       = $upload_dir['path'] . '/' . $image_name;

		if ( ! $wp_filesystem->move( $temp_file, $file ) ) {
			unlink( $temp_file );

			return new WP_Error( 'file_move_error', 'Failed to move file to upload directory' );
		}

		$wp_filetype = wp_check_filetype( $file, null );
		$attachment  = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $file ),
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $image_name ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $file );

		if ( is_wp_error( $attach_id ) ) {
			unlink( $file );

			return $attach_id;
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}


	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function create_course_feature_image( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$data            = array();
		$generate_prompt = OpenAi::get_course_image_create_prompt( $params );

		if ( empty( $params['prompt'] ) ) {
			$prompt = $generate_prompt['prompt'];
		} else {
			$prompt = $params['prompt'];
		}

		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

		if ( empty( $prompt ) ) {
			return $this->error( __( 'Invalid prompt', 'learnpress' ), 400, $data );
		}

		$model = LP_Settings::instance()->get( 'open_ai_image_model_type' );
		$body  = array(
			'prompt'          => $prompt,
			'n'               => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'size'            => $params['size'] ?? '1024x1024',
			'quality'         => $params['quality'] ?? 'standard',
			'response_format' => 'b64_json',
			'model'           => $model,
		);

		$args = array(
			'method'  => 'POST',
			'timeout' => 3600,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->secret_key,
			),
			'body'    => json_encode( $body ),
		);

		$response = wp_remote_request( $this->create_image_url, $args );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_codes(), $data );
		}

		$result = wp_remote_retrieve_body( $response );
		$result = json_decode( $result, true );


		if ( isset( $result['error'] ) ) {
			return $this->error( $result['error']['message'], $result['error']['code'], $data );
		}

		$data['content'] = $result['data'] ?? array();

		return $this->success( esc_html__( 'Generate course feature image successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function edit_course_feature_image( WP_REST_Request $request ) {
		$params = $request->get_params();
		$data   = array();

		if ( empty( $params['logo'] ) ) {
			return $this->error( __( 'Invalid logo', 'learnpress' ), 400, $data );
		}

		if ( empty( $params['mask'] ) ) {
			return $this->error( __( 'Invalid mask', 'learnpress' ), 400, $data );
		}

		$generate_prompt = OpenAi::get_course_image_edit_prompt( $params );

		if ( empty( $params['prompt'] ) ) {
			$prompt = $generate_prompt['prompt'];
		} else {
			$prompt = $params['prompt'];
		}

		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

		if ( empty( $prompt ) ) {
			return $this->error( __( 'Invalid prompt', 'learnpress' ), 400, $data );
		}

		$logo_parts     = explode( ',', $params['logo'] );
		$logo_data      = base64_decode( $logo_parts[1] );
		$logo_temp_file = tempnam( sys_get_temp_dir(), 'img' );
		file_put_contents( $logo_temp_file, $logo_data );

		$mask_parts     = explode( ',', $params['mask'] );
		$mask_data      = base64_decode( $mask_parts[1] );
		$mask_temp_file = tempnam( sys_get_temp_dir(), 'img' );
		file_put_contents( $mask_temp_file, $mask_data );

		$args = array(
			'image'           => curl_file_create( $logo_temp_file, 'image/png', basename( $logo_temp_file ) ),
			'mask'            => curl_file_create( $mask_temp_file, 'image/png', basename( $mask_temp_file ) ),
			'prompt'          => $prompt,
			'n'               => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'size'            => $params['size'] ?? '256x256',
			'response_format' => 'b64_json',
			'model'           => 'dall-e-2'
		);

		$curl_info = [
			CURLOPT_URL            => $this->edit_image_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 3600,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $args,
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $this->secret_key,
				'Content-Type: multipart/form-data'
			),
		];

		$curl = curl_init();

		curl_setopt_array( $curl, $curl_info );
		$response  = curl_exec( $curl );
		$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );


		curl_close( $curl );

		if ( ! $response ) {
			$this->error( curl_error( $curl ), $http_code, $data );
		}
		$result = json_decode( $response, true );

		$data['content'] = $result['data'];

		return $this->success( esc_html__( 'Generate course feature image successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function generate_text( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$generate_prompt = OpenAi::get_completions_prompt( $params );
		$data            = array();

		if ( empty( $params['prompt'] ) ) {
			$prompt = $generate_prompt['prompt'];
		} else {
			$prompt = $params['prompt'];
		}

		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

		$args = array(
			'model'             => $this->text_model_type,
			'frequency_penalty' => floatval( $this->frequency_penalty ),
			'presence_penalty'  => floatval( $this->presence_penalty ),
			'n'                 => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'temperature'       => floatval( $this->creativity_level )
		);

		//Max tokens
		if ( ! empty( $this->max_token ) ) {
			$args['max_tokens'] = intval( $this->max_token );
		}

		if ( in_array( $this->text_model_type, array(
			'chatgpt-4o-latest',
			'gpt-4o',
			'gpt-4o-mini',
			'gpt-4',
			'gpt-3.5-turbo'
		) ) ) {
			$this->text_model_type_url = 'https://api.openai.com/v1/chat/completions';
			$args['messages']          = array(
				array(
					"role"    => "system",
					"content" => "You are an AI assistant specialized in education and course design."
				),
				array(
					"role"    => "user",
					"content" => $prompt
				)
			);
		} else if ( in_array( $this->text_model_type, array(
			'gpt-3.5-turbo-instruct',
		) ) ) {
			$this->text_model_type_url = 'https://api.openai.com/v1/completions';
			$args['prompt']            = $prompt;
		} else {
			return $this->error( esc_html__( 'Invalid model', 'learnpress' ), 400, $data );
		}

		$response = wp_remote_post( $this->text_model_type_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => json_encode( $args ),
			'timeout' => 3600,
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_code(), $data );
		}

		$body = wp_remote_retrieve_body( $response );

		$result = json_decode( $body, true );

		if ( isset( $result['error'] ) ) {
			return $this->error( $result['error']['message'], $result['error']['code'], $data );
		}

		$content = array();
		if ( isset( $result['choices'] ) ) {
			$choices = $result['choices'];
			if ( is_array( $choices ) ) {
				foreach ( $choices as $choice ) {
					$text_content = '';
					if ( isset( $choice['message']['content'] ) ) {
						$text_content = $choice['message']['content'];
					} elseif ( isset( $choice['text'] ) ) {
						$text_content = $choice['text'];
					}

					if ( isset( $params['data_return'] ) && $params['data_return'] === 'json' ) {
						$json_text_content = preg_replace( '/^```json\s*|\s*```$/m', '', $text_content );
						$json_text_content = trim( $json_text_content );
						$text_content      = json_decode( $json_text_content, true );

						if ( json_last_error() ) {
							return $this->error( esc_html__( 'Invalid json: ' . $json_text_content, 'learnpress' ), 400, $data );
						}
					}

					$content[] = $text_content;
				}
			}
		}

		$data ['content'] = $content;
		$success_text     = sprintf( __( 'Generate %s successfully!', 'learnpress' ), str_replace( '-', ' ', $params['type'] ) );

		return $this->success( $success_text, $data );
	}

	/**
	 * @param string $msg
	 * @param $status_code
	 * @param $data
	 *
	 * @return WP_REST_Response
	 */
	public function error( string $msg = '', $status_code = 404, array $data = [] ) {
		return new WP_REST_Response(
			array(
				'status'      => 'error',
				'msg'         => $msg,
				'status_code' => $status_code,
				'data'        => $data
			),
		//            $status_code
		);
	}

	/**
	 * @param string $msg
	 * @param array $data
	 *
	 * @return WP_REST_Response
	 */
	public function success( string $msg = '', array $data = array() ) {
		return new WP_REST_Response(
			array(
				'status' => 'success',
				'msg'    => $msg,
				'data'   => $data,
			),
			200
		);
	}
}

