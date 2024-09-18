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
		$params = $request->get_params();

		$generate_prompt = OpenAi::get_course_image_create_prompt( $params );

		if ( empty( $params['prompt'] ) ) {
			$prompt = $generate_prompt['prompt'];
		} else {
			$prompt = $params['prompt'];
		}

		if ( empty( $prompt ) ) {
			return $this->error( __( 'Invalid prompt', 'learnpress' ), 400 );
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
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->secret_key,
			),
			'body'    => json_encode( $body ),
		);

		$response = wp_remote_request( $this->create_image_url, $args );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), $response->get_error_codes() );
		}

		$result = wp_remote_retrieve_body( $response );
		$result = json_decode( $result, true );

		$data['content'] = $result['data'] ?? array();
		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

		return $this->success( esc_html__( 'Generate course feature image successfully!', 'learnpress' ), $data );
	}


	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function edit_course_feature_image( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( empty( $params['logo'] ) ) {
			return $this->error( __( 'Invalid logo', 'learnpress' ), 400 );
		}

		if ( empty( $params['mask'] ) ) {
			return $this->error( __( 'Invalid mask', 'learnpress' ), 400 );
		}

		$generate_prompt = OpenAi::get_course_image_edit_prompt( $params );

		if ( $params['prompt'] ) {
			$prompt = $params['prompt'];
		} else {
			$prompt = $generate_prompt['prompt'];
		}

		if ( empty( $prompt ) ) {
			return $this->error( __( 'Invalid prompt', 'learnpress' ), 400 );
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
			CURLOPT_TIMEOUT        => 60,
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
			$this->error( curl_error( $curl ), $http_code );
		}
		$result = json_decode( $response, true );

		$data['content'] = $result['data'];
		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

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

		if ( $params['prompt'] ) {
			$prompt = $params['prompt'];
		} else {
			$prompt = $generate_prompt['prompt'];
		}

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

		if ( empty( $params['prompt'] ) ) {
			$data ['prompt'] = $generate_prompt['prompt_html'];
		}

		$data ['content'] = $content;
		$success_text     = sprintf( __( 'Generate %s successfully!', 'learnpress' ), str_replace( '-', ' ', $params['type'] ) );

		return $this->success( $success_text, $data );
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

