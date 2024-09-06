<?php

class LP_REST_Admin_OpenAI_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;
	protected $url = 'https://api.openai.com/v1/chat/completions';
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
		$this->image_model_type = $lp_settings->get( 'open_ai_text_model_type', 'dall-e-3' );

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
			'get-course-title' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_course_title' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		parent::register_routes();
	}

	public function check_admin_permission() {
		return LP_Abstract_API::check_admin_permission();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void|WP_REST_Response
	 */
	public function get_course_title( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$generate_prompt = $this->generate_prompt( $params );
		$prompt          = $generate_prompt['prompt'];

		$args = array(
			'model'             => $this->text_model_type,
			'frequency_penalty' => floatval($this->frequency_penalty),
			'presence_penalty'  => floatval($this->presence_penalty),
			'max_tokens'        => intval($this->max_token),
			'n'                 => $params['outputs'] ? intval( $params['outputs'] ) : 1,
			'temperature'       => floatval($this->creativity_level)
		);

		if ( in_array( $this->text_model_type, array(
			'chatgpt-4o-latest',
			'gpt-4o',
			'gpt-4o-mini',
			'gpt-4',
			'gpt-3.5-turbo'
		) ) ) {
			$this->url        = 'https://api.openai.com/v1/chat/completions';
			$args['messages'] = array(
				array(
					"role"    => "user",
					"content" => $prompt
				)
			);
		} else if ( in_array( $this->text_model_type, array(
			'gpt-3.5-turbo-instruct',
			'babbage-002',
			'davinci-002'
		) ) ) {
			$this->url        = 'https://api.openai.com/v1/completions';
			$args['messages'] = array(
				array(
					"role"    => "user",
					"content" => $prompt
				)
			);
		} else {
			$this->error( esc_html__( 'Invalid model', 'learnpress' ), 400 );
			$data['prompt'] = $prompt;
		}

		$response = wp_remote_post( $this->url, array(
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
					}
				}
			}
		}

		$data ['prompt']  = $generate_prompt['prompt_html'];
		$data ['content'] = $content;

		return $this->success( esc_html( 'Generate course title successfully!', 'learnpress' ), $data );
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public function generate_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['audience'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}


		$prompt_html = '';

		$prompt = 'Create a course title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the course title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<p>Create a course title based on the following:</br>';
		$prompt_html .= 'Topic: ' . $topic.'</br>';
		$prompt_html .= 'Goal: ' . $goal . '</br>';
		$prompt_html .= 'Audience: ' . $audience . '</br>';
		$prompt_html .= 'Tone: ' . $tone . '</br>';
		$prompt_html .= 'Language: ' . $language . '</br>';
		$prompt_html .= 'Please provide only the course title without any additional explanation or details, and do not include quotation marks.';
		$prompt_html .= '</p>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

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

