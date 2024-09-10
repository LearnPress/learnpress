<?php

use LearnPress\Helpers\OpenAi;

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
			'course-title'       => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_course_title' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'course-des'         => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_course_des' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'edit-feature-image' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'edit_course_feature_image' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
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
	 * @return void|WP_REST_Response
	 */
	public function edit_course_feature_image( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['logo'] ) ) {
			return $this->error( __( 'Logo not found', 'learnpress' ), 400 );
		}

		$generate_prompt = OpenAi::get_course_image_edit_prompt( $params );

		$prompt          = $generate_prompt['prompt'];

		$model = 'dall-e-2';


		$multipart  = array(
			array(
				'name'     => 'model',
				'contents' => $model,
			),
			array(
				'name'     => 'prompt',
				'contents' => $prompt,
			),
			array(
				'name'     => 'n',
				'contents' => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			),
			array(
				'name'     => 'size',
				'contents' => $params['size'] ? intval( $params['size'] ) : '1024x1024',
			),
			array(
				'name'     => 'image',
				'contents' => fopen($params['logo'], 'r'), // Đọc file hình ảnh
				'headers'  => array(
					'Content-Type' => 'image/png',
					'Content-Disposition' => 'form-data; name="image"; filename="image.png"',
				),
			)
		);

		$response = wp_remote_post( $this->edit_image_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'multipart/form-data',
			),
			'multipart'    => $multipart,
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_code() );
		}

		$body   = wp_remote_retrieve_body( $response );
//		$result = json_decode( $body, true );

		$data = array();

		$image = array();
		echo '<pre>';
		print_r($body);
		echo '</pre>';
		die;

		$data ['prompt']  = $generate_prompt['prompt_html'];
		$data ['image'] = $content;

		return $this->success( esc_html__( 'Generate course title successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void|WP_REST_Response
	 */
	public function create_course_title( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$generate_prompt = OpenAi::get_course_title_prompt( $params );
		$prompt          = $generate_prompt['prompt'];

		$args = array(
			'model'             => $this->text_model_type,
			'frequency_penalty' => floatval( $this->frequency_penalty ),
			'presence_penalty'  => floatval( $this->presence_penalty ),
			'max_tokens'        => intval( $this->max_token ),
			'n'                 => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'temperature'       => floatval( $this->creativity_level )
		);

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
			return $this->error( esc_html__( 'Invalid model', 'learnpress' ), 400 );
		}

		$response = wp_remote_post( $this->text_model_type_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => json_encode( $args ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_code() );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		$data = array();

		$content = array();
		if ( isset( $result['choices'] ) ) {
			$choices = $result['choices'];
			if ( is_array( $choices ) ) {
				foreach ( $choices as $choice ) {
					if ( isset( $choice['message']['content'] ) ) {
						$content[] = $choice['message']['content'];
					} elseif ( isset( $choice['text'] ) ) {
						$content[] = $choice['text'];
					}
				}
			}
		}

		$data ['prompt']  = $generate_prompt['prompt_html'];
		$data ['content'] = $content;

		return $this->success( esc_html__( 'Generate course title successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function create_course_des( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$generate_prompt = OpenAi::get_course_des_prompt( $params );
		$prompt          = $generate_prompt['prompt'];

		$args = array(
			'model'             => $this->text_model_type,
			'frequency_penalty' => floatval( $this->frequency_penalty ),
			'presence_penalty'  => floatval( $this->presence_penalty ),
			'max_tokens'        => intval( $this->max_token ),
			'n'                 => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'temperature'       => floatval( $this->creativity_level )
		);

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
			return $this->error( esc_html__( 'Invalid model', 'learnpress' ), 400 );
		}

		$response = wp_remote_post( $this->text_model_type_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => json_encode( $args ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_code() );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		$data = array();

		$content = array();
		if ( isset( $result['choices'] ) ) {
			$choices = $result['choices'];
			if ( is_array( $choices ) ) {
				foreach ( $choices as $choice ) {
					if ( isset( $choice['message']['content'] ) ) {
						$content[] = $choice['message']['content'];
					} elseif ( isset( $choice['text'] ) ) {
						$content[] = $choice['text'];
					}
				}
			}
		}

		$data ['prompt']  = $generate_prompt['prompt_html'];
		$data ['content'] = $content;

		return $this->success( esc_html__( 'Generate course title successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param string $msg
	 * @param $status_code
	 *
	 * @return WP_REST_Response
	 */
	public function error( string $msg = '', $status_code = 404 ) {
		return new WP_REST_Response(
			array(
				'status'      => 'error',
				'msg'         => $msg,
				'status_code' => $status_code,
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

