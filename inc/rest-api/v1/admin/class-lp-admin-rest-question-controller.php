<?php
/**
 * Class LP_REST_Question_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Admin_Question_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;

	/**
	 * @var LP_Course
	 */
	protected $course = null;

	/**
	 * @var LP_Course_Item|LP_Quiz|LP_Lesson
	 */
	protected $item = null;

	/**
	 * @var LP_User_Item_Course
	 */
	protected $user_course = null;

	/**
	 * @var LP_User_Item|LP_User_Item_Quiz
	 */
	protected $user_item = null;

	public function __construct() {
		$this->namespace = 'lp/a/v1';
		$this->rest_base = 'question';

		parent::__construct();

		include_once LP_PLUGIN_PATH . '/inc/admin/editor/class-lp-admin-editor.php';
		include_once LP_PLUGIN_PATH . '/inc/admin/editor/class-lp-admin-editor-question.php';
	}

	public function register_routes() {
		$this->routes = array(
			''                            => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),

			'update'                      => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => '__return_true',
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					// 'args'     => $this->get_item_endpoint_args()
				),
			),
			'(?P<id>[\d]+)/add-option'    => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'add_option' ),
					'permission_callback' => '__return_true',
				),
			),
			'(?P<id>[\d]+)/remove-option' => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'remove_option' ),
					'permission_callback' => '__return_true',
				),
			),
			'(?P<id>[\d]+)/update-option' => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_option' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function update( $request ) {
		$response = array(
			$_REQUEST,
			'xxxx' => $request,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Add new answer option to DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function add_option( $request ) {
		$_REQUEST = array_merge( $_POST, $request->get_params() );
		$editor   = new LP_Admin_Editor_Question();
		$result   = $editor->dispatch();

		if ( is_array( $result ) ) {
			$result = end( $result );
		}

		$response = array(
			'success' => $result !== false,
			'result'  => $result,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Remove answer option from DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function remove_option( $request ) {
		$_REQUEST = array_merge( $_POST, $request->get_params() );
		$editor   = new LP_Admin_Editor_Question();
		$result   = $editor->dispatch();

		if ( is_array( $result ) ) {
			$result = end( $result );
		}

		$response = array(
			'success' => $result !== false,
			'result'  => $result,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Update option data to DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function update_option( $request ) {
		$_REQUEST = array_merge( $_POST, $request->get_params() );
		$editor   = new LP_Admin_Editor_Question();
		$result   = $editor->dispatch();

		if ( is_array( $result ) ) {
			$result = end( $result );
		}

		if ( $result && ! empty( $_REQUEST['blanks'] ) ) {
			learn_press_update_question_answer_meta( $_REQUEST['question_answer_id'], '_blanks', $_REQUEST['blanks'] );
		}

		$response = array(
			'success' => $result !== false,
			'result'  => $result,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get args for user item endpoints.
	 *
	 * @return array
	 */
	public function get_item_endpoint_args() {
		return array(
			'item_id'   => array(
				'description'       => __( 'The ID of course item object.', 'learnpress' ),
				'type'              => 'int',
				'validate_callback' => array( $this, 'validate_arg' ),
				'required'          => true,
			),
			'course_id' => array(
				'description'       => __( 'The ID of course object.', 'learnpress' ),
				'type'              => 'int',
				'validate_callback' => array( $this, 'validate_arg' ),
				'required'          => true,
			),
		);
	}

	/**
	 * Validation callback to verify rest args.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return bool|WP_Error
	 */
	public function validate_arg( $value, $request, $param ) {
		$attributes = $request->get_attributes();

		if ( ! isset( $attributes['args'][ $param ] ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s was not registered as a request argument.', 'learnpress' ), $param ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Sanitize callback.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return mixed
	 */
	public function sanitize_arg( $value, $request, $param ) {
		switch ( $param ) {
			case 'user_id':
			case 'item_id':
			case 'course_id':
				return absint( $value );
		}

		return $value;
	}

	public function check_admin_permission() {
		return LP_REST_Authentication::check_admin_permission();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$response = array();

		return rest_ensure_response( $response );
	}
}
