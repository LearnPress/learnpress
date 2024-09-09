<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Users_Controller
 *
 * @since 4.2.7
 */
class EditCourseApi extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin/edit';
		$this->rest_base = 'course';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'html-curriculum/(?P<course_id>[\d]+)' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'html_curriculum' ),
					'permission_callback' => '',
				),
			),
			'update-section'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_section' ),
					'permission_callback' => '',
				),
			),
			'delete-section'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_section' ),
					'permission_callback' => '',
				),
			),
			'add-section'                          => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_section' ),
					'permission_callback' => '',
				),
			),
			'update-order-section'                 => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_order_section' ),
					'permission_callback' => '',
				),
			),
			'update-section-item'                  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_section_item' ),
					'permission_callback' => '',
				),
			),
			'remove-item-in-section'               => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'remove_item_in_section' ),
					'permission_callback' => '',
				),
			),
			'delete-section-item'                  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_section_item' ),
					'permission_callback' => '',
				),
			),
			'add-new-section-item'                 => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_new_section_item' ),
					'permission_callback' => '',
				),
			),
			'update-order-section-item'            => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_order_section_item' ),
					'permission_callback' => '',
				),
			),
		);

		parent::register_routes();
	}

	public function html_curriculum( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$course_id            = $params['course_id'] ?? 0;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course Id available!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );

			if ( empty( $course ) ) {
				throw new Exception( esc_html__( 'No courses found!', 'learnpress' ) );
			}

			$curriculum = $course->get_curriculum_raw();
			foreach ( $curriculum as $index => $curriculum_item ) {
				$curriculum_id = $curriculum_item['id'] ?? 0;
				$title         = $curriculum_item['title'] ?? '';
				$desc          = $curriculum_item['section_description'] ?? '';
				$count_item    = count( $curriculum_item['items'] ) ?? 0;
				$items         = $curriculum_item['items'] ?? array();
				$section_order = $curriculum_item['order'] ?? 0;
				ob_start();

				Template::instance()->get_admin_template(
					'course/curriculum/section',
					compact( 'curriculum_id', 'title', 'desc', 'count_item', 'items', 'section_order' )
				);

				$response->data->html[] = ob_get_clean();
				$response->status       = 'success';
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function update_order_section( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$course_id   = $params['courseId'] ?? false;
		$section_ids = $params['sectionIds'] ?? false;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course Id available!', 'learnpress' ) );
			}

			if ( empty( $section_ids ) ) {
				throw new Exception( esc_html__( 'No Section Ids available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( $course_id );
			$section->update_sections_order( $section_ids );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}
		return $response;
	}

	public function update_section( WP_REST_Request $request ) {
		$response          = new LP_REST_Response();
		$params            = $request->get_params();
		$section_id        = $params['sectionId'] ?? 0;
		$section_name      = $params['title'] ?? '';
		$section_order     = $params['order'] ?? 0;
		$section_desc      = $params['desc'] ?? 0;
		$section_course_id = $params['sectionCourseId'] ?? 0;

		try {
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$filter  = new LP_Section_Filter();
			$section = LP_Section_DB::getInstance();

			$filter->where[] = $section->wpdb->prepare( 'AND section_id = %d', $section_id );

			if ( ! empty( $section_course_id ) ) {
				$filter->set[] = $section->wpdb->prepare( 'section_course_id = %s', $section_course_id );
			}

			if ( ! empty( $section_name ) ) {
				$filter->set[] = $section->wpdb->prepare( 'section_name = %s', $section_name );
			}

			if ( ! empty( $section_order ) ) {
				$filter->set[] = $section->wpdb->prepare( 'section_order = %d', $section_order );
			}

			if ( ! empty( $section_desc ) ) {
				$filter->set[] = $section->wpdb->prepare( 'section_description = %s', $section_desc );
			}

			$section->update( $filter );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function delete_section( WP_REST_Request $request ) {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$section_id = $params['sectionId'] ?? 0;

		try {
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( '' );
			$section->delete( $section_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_section( WP_REST_Request $request ) {
		$response     = new LP_REST_Response();
		$params       = $request->get_params();
		$course_id    = $params['courseId'] ?? '';
		$section_name = $params['title'] ?? '';
		$section_desc = $params['desc'] ?? '';
		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course Id available!', 'learnpress' ) );
			}

			if ( empty( $section_name ) ) {
				throw new Exception( esc_html__( 'No Section name available!', 'learnpress' ) );
			}

			$section      = new LP_Section_CURD( $course_id );
			$section_data = [
				'section_name'        => $section_name,
				'section_description' => $section_desc,
			];

			$new_section                   = $section->create( $section_data );
			$response->data->section_id    = $new_section['section_id'];
			$response->data->section_order = $new_section['section_order'];
			$curriculum_id                 = $new_section['section_id'] ?? 0;
			$title                         = $new_section['section_name'] ?? '';
			$desc                          = $new_section['section_description'] ?? '';
			$count_item                    = 0;
			$items                         = array();
			$section_order                 = $new_section['section_order'] ?? 0;
				ob_start();

				Template::instance()->get_admin_template(
					'course/curriculum/section',
					compact( 'curriculum_id', 'title', 'desc', 'count_item', 'items', 'section_order' )
				);

				$response->data->html[] = ob_get_clean();
			$response->status           = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function update_order_section_item( WP_REST_Request $request ) {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$course_id  = $params['courseId'] ?? false;
		$section_id = $params['sectionId'] ?? false;
		$items      = $params['items'] ?? false;
		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No section ID available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No section ID available!', 'learnpress' ) );
			}

			if ( empty( $items ) ) {
				throw new Exception( esc_html__( 'No Items available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( $course_id );
			$section->update_section_items( $section_id, $items );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function update_section_item( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$params   = $request->get_params();
		$item_id  = $params['itemId'] ?? false;
		$title    = $params['title'] ?? false;
		$preview  = $params['preview'] ?? false;

		try {
			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'No Section Item ID available!', 'learnpress' ) );
			}

			$data['id'] = $item_id;

			if ( ! empty( $title ) ) {
				$data['title'] = $title;
			}

			if ( ! empty( $preview ) ) {
				$data['preview'] = $preview;
			}

			$section = new LP_Section_CURD( '' );
			$section->update_item( $data );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}
		return $response;
	}

	public function remove_item_in_section( WP_REST_Request $request ) {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$item_id    = $params['itemId'] ?? false;
		$section_id = $params['sectionId'] ?? false;

		try {
			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'No Section Item ID available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( '' );
			$section->remove_section_item( $section_id, $item_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function delete_section_item( WP_REST_Request $request ) {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$item_id    = $params['itemId'] ?? false;
		$section_id = $params['sectionId'] ?? false;

		try {
			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'No Section Item ID available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( '' );
			$db      = LP_Database::getInstance();
			wp_delete_post( $item_id );
			$section->remove_section_item( $section_id, $item_id );
			$db->wpdb->delete( $db->tb_postmeta, array( 'post_id' => $item_id ) );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_new_section_item( WP_REST_Request $request ) {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$item       = $params['item'] ?? false;
		$section_id = $params['sectionId'] ?? false;
		$course_id  = $params['courseId'] ?? false;

		try {
			if ( empty( $item ) ) {
				throw new Exception( esc_html__( 'No Section Item available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course ID available!', 'learnpress' ) );
			}

			$section    = new LP_Section_CURD( $course_id );
			$data_item  = $section->new_item( $section_id, $item );
			$html       = '';
			$total_item = 0;

			if ( ! empty( $data_item ) && is_array( $data_item ) ) {
				$total_item = count( $data_item );
				$data_new   = end( $data_item );
				$title      = $data_new['title'] ?? '';
				$item_id    = $data_new['id'] ?? 0;
				$type       = $data_new['type'] ?? 0;
				$item_order = $data_new['item_order'] ?? 0;
				$item_link  = get_edit_post_link( $item_id ) ?? '#';
				$preview    = $data_new['preview'];
				ob_start();
				Template::instance()->get_admin_template(
					'course/curriculum/section-item',
					compact( 'item_id', 'title', 'type', 'item_order', 'item_link', 'preview' )
				);
				$html = ob_get_clean();
			}
			$response->data->total = sprintf( '%s %s', $total_item, esc_html__( 'Items', 'learnpress' ) );
			$response->data->html  = $html;
			$response->status      = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
