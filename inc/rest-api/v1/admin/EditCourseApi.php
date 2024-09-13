<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;

/**
 * Class EditCourseApi
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
					'callback'            => array( $this, 'get_html_curriculum' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'update-section'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'delete-section'                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'add-section'                          => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'update-order-section'                 => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_order_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'update-section-item'                  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_section_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'remove-item-in-section'               => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'remove_item_in_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'delete-section-item'                  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'delete_section_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'add-new-section-item'                 => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'add_new_section_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'update-order-section-item'            => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'update_order_section_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'search-items'                         => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'search_items' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	public function check_permission() {
		// return LP_Abstract_API::check_admin_permission();
		return true;
	}

	public function clear_cache_course( $course_id ) {
		$response = new LP_REST_Response();

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course ID available!', 'learnpress' ) );
			}

			$bg = LP_Background_Single_Course::instance();
			$bg->data(
				array(
					'handle_name' => 'save_post',
					'course_id'   => $course_id,
					'data'        => [],
				)
			)->dispatch();
		} catch ( \Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function get_html_curriculum( WP_REST_Request $request ) {
		$response             = new LP_REST_Response();
		$response->data->html = array();
		$params               = $request->get_params();
		$course_id            = $params['course_id'] ?? 0;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course Id available!', 'learnpress' ) );
			}

			$course = CourseModel::find( $course_id, true );
			if ( ! empty( $course ) ) {
				$course_section_items = $course->get_section_items();
			} else {
				$course               = learn_press_get_course( $course_id );
				$course_section_items = $course->get_curriculum_raw();
			}

			if ( empty( $course ) ) {
				throw new Exception( esc_html__( 'No courses found!', 'learnpress' ) );
			}

			if ( empty( $course_section_items ) ) {
				throw new Exception( esc_html__( 'No section found!', 'learnpress' ) );
			}

			foreach ( $course_section_items as $index => $course_section_item ) {
				$section_id    = $course_section_item->id ?? 0;
				$title         = $course_section_item->title ?? '';
				$desc          = $course_section_item->section_description ?? '';
				$count_item    = count( $course_section_item->items ) ?? 0;
				$items         = $course_section_item->items ?? array();
				$section_order = $course_section_item->order ?? 0;
				ob_start();

				Template::instance()->get_admin_template(
					'course/curriculum/section',
					compact( 'section_id', 'title', 'desc', 'count_item', 'items', 'section_order' )
				);

				$response->data->html[] = ob_get_clean();
			}

			$response->status = 'success';
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
			$this->clear_cache_course( $course_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}
		return $response;
	}

	public function update_section( WP_REST_Request $request ) {
		$response      = new LP_REST_Response();
		$params        = $request->get_params();
		$section_id    = $params['sectionId'] ?? 0;
		$section_name  = $params['title'] ?? '';
		$section_order = $params['order'] ?? 0;
		$section_desc  = $params['desc'] ?? 0;
		$course_id     = $params['courseId'] ?? 0;

		try {
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$filter  = new LP_Section_Filter();
			$section = LP_Section_DB::getInstance();

			$filter->where[] = $section->wpdb->prepare( 'AND section_id = %d', $section_id );

			if ( ! empty( $course_id ) ) {
				$filter->set[] = $section->wpdb->prepare( 'section_course_id = %s', $course_id );
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
			$this->clear_cache_course( $course_id );
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
		$course_id  = $params['courseId'] ?? 0;

		try {
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( '' );
			$section->delete( $section_id );
			$this->clear_cache_course( $course_id );
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
			$section_id                    = $new_section['section_id'] ?? 0;
			$title                         = $new_section['section_name'] ?? '';
			$desc                          = $new_section['section_description'] ?? '';
			$count_item                    = 0;
			$items                         = array();
			$section_order                 = $new_section['section_order'] ?? 0;

			ob_start();
			Template::instance()->get_admin_template(
				'course/curriculum/section',
				compact( 'section_id', 'title', 'desc', 'count_item', 'items', 'section_order' )
			);
			$response->data->html[] = ob_get_clean();

			$this->clear_cache_course( $course_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function update_order_section_item( WP_REST_Request $request ) {
		$response    = new LP_REST_Response();
		$params      = $request->get_params();
		$course_id   = $params['courseId'] ?? false;
		$section_id  = $params['sectionId'] ?? false;
		$items       = $params['items'] ?? false;
		$items_added = $params['itemAddNew'] ?? false;

		try {

			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No course ID available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No section ID available!', 'learnpress' ) );
			}

			if ( empty( $items ) ) {
				throw new Exception( esc_html__( 'No Items available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( $course_id );
			$items   = $section->update_section_items( $section_id, $items );
			if ( ! empty( $items_added ) && is_array( $items_added ) ) {
				foreach ( $items_added as $key => $item ) {
					$title      = $item['title'] ?? '';
					$item_id    = $item['id'] ?? 0;
					$type       = $item['type'] ?? 0;
					$item_order = -1;
					$item_link  = get_edit_post_link( $item_id ) ?? '#';
					$preview    = false;
					ob_start();
					Template::instance()->get_admin_template(
						'course/curriculum/section-item',
						compact( 'item_id', 'title', 'type', 'item_order', 'item_link', 'preview' )
					);
					$html                   = ob_get_clean();
					$response->data->html[] = $html;
				}
			}
			$this->clear_cache_course( $course_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function update_section_item( WP_REST_Request $request ) {
		$response  = new LP_REST_Response();
		$params    = $request->get_params();
		$item_id   = $params['itemId'] ?? false;
		$title     = $params['title'] ?? false;
		$preview   = $params['preview'] ?? false;
		$course_id = $params['courseId'] ?? false;
		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course ID available!', 'learnpress' ) );
			}

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
			$this->clear_cache_course( $course_id );
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
		$course_id  = $params['courseId'] ?? false;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Courses ID available!', 'learnpress' ) );
			}

			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'No Section Item ID available!', 'learnpress' ) );
			}

			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'No Section ID available!', 'learnpress' ) );
			}

			$section = new LP_Section_CURD( '' );
			$section->remove_section_item( $section_id, $item_id );
			$this->clear_cache_course( $course_id );
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
		$course_id  = $params['courseId'] ?? false;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Courses ID available!', 'learnpress' ) );
			}

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
			$this->clear_cache_course( $course_id );
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
			$response->data->html = $html;
			$this->clear_cache_course( $course_id );
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function search_items( WP_REST_Request $request ) {
		$response  = new LP_REST_Response();
		$params    = $request->get_params();
		$course_id = $params['courseId'] ?? '';
		$query     = $params['query'] ?? '';
		$type      = $params['itemType'] ?? '';
		$page      = $params['page'] ?? 1;
		$exclude   = $params['exclude'] ?? '';

		if ( ! class_exists( 'LP_Modal_Search_Items' ) ) {
			require_once LP_PLUGIN_PATH . '/inc/admin/class-lp-modal-search-items.php';
		}

		try {
			if ( empty( $type ) ) {
				throw new Exception( esc_html__( 'No Item type available!', 'learnpress' ) );
			}

			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course Id available!', 'learnpress' ) );
			}

			$search = new LP_Modal_Search_Items(
				array(
					'type'       => $type,
					'context'    => 'course',
					'context_id' => $course_id,
					'term'       => $query,
					'limit'      => apply_filters( 'learn-press/course-editor/choose-items-limit', 10 ),
					'paged'      => $page,
					'exclude'    => $exclude,
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
}
