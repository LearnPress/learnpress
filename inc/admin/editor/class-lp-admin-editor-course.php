<?php

/**
 * Class LP_Admin_Editor_Course
 *
 * @since 3.0.2
 */
class LP_Admin_Editor_Course extends LP_Admin_Editor {

	/**
	 * @var LP_Course_CURD
	 */
	protected $course_curd = null;

	/**
	 * @var LP_Section_CURD
	 */
	protected $section_curd = null;

	/**
	 * @var LP_Course
	 */
	protected $course = null;

	/**
	 * LP_Admin_Editor_Course constructor.
	 */
	public function __construct() {

	}

	/**
	 * Do the action depending on ajax calls with params
	 *
	 * @return bool|WP_Error
	 */
	public function dispatch() {
		check_ajax_referer( 'learnpress_update_curriculum', 'nonce' );

		$args      = wp_parse_args(
			$_REQUEST,
			array(
				'id'   => 0,
				'type' => '',
			)
		);
		$course_id = $args['id'] ?? 0;
		$course    = learn_press_get_course( $course_id );

		if ( ! $course ) {
			return false;
		}

		$this->course       = $course;
		$this->course_curd  = new LP_Course_CURD();
		$this->section_curd = new LP_Section_CURD( $course_id );
		$this->result       = array( $args['type'] );

		$this->call( $args['type'], array( $args ) );
		//LP_Course_Post_Type::instance()->save_post( $course_id, null, true );
		$bg = LP_Background_Single_Course::instance();
		$bg->data(
			array(
				'handle_name' => 'save_post',
				'course_id'   => $course_id,
				'data'        => [],
			)
		)->dispatch();

		return $this->get_result();
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function draft_course( $args = array() ) {
		$new_course = ! empty( $args['course'] ) ? $args['course'] : false;
		$new_course = json_decode( wp_unslash( $new_course ), true );

		if ( ! $new_course ) {
			return false;
		}

		$title   = $new_course['title'] ? $new_course['title'] : __( 'New Course', 'learnpress' );
		$content = $new_course['content'] ? $new_course['content'] : '';

		$args = array(
			'id'      => $this->course->get_id(),
			'status'  => 'draft',
			'title'   => $title,
			'content' => $content,
		);

		$this->course_curd->create( $args );

		return true;
	}

	/**
	 * @param array $args
	 */
	public function hidden_sections( $args ) {
		$hidden = ! empty( $args['hidden'] ) ? $args['hidden'] : false;
		update_post_meta( $this->course->get_id(), '_admin_hidden_sections', $hidden );
	}

	/**
	 * Sort sections
	 *
	 * @param array $args
	 */
	public function sort_sections( $args = array() ) {
		$order = ! empty( $args['order'] ) ? $args['order'] : false;
		$order = json_decode( wp_unslash( $order ), true );

		if ( ! $order ) {
			return;
		}

		//$this->course ? $this->course->get_sections() : '';
		$this->result = $this->section_curd->update_sections_order( $order );

		// update final quiz
		//$this->section_curd->update_final_item();
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function update_section( $args = array() ) {
		$section = $args['section'] ?? false;
		$section = json_decode( wp_unslash( $section ), true );

		if ( ! $section ) {
			return false;
		}

		if ( ! isset( $section['course_id'] ) && ! isset( $section['id'] ) ) {
			return false;
		}

		$data = array(
			'section_id'          => $section['id'],
			'section_name'        => $section['title'],
			'section_description' => $section['description'],
			'section_order'       => $section['order'],
			'section_course_id'   => $this->course->get_id(),
		);

		$this->result = $this->section_curd->update( $data );

		return $this->result;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function remove_section( $args = array() ) {
		$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;

		if ( ! $section_id ) {
			return false;
		}

		$this->section_curd->delete( $section_id );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function new_section( $args = array() ) {
		$section_name = $args['section_name'] ?? '';
		$temp_id      = $args['temp_id'] ?? 0;

		$args = array(
			'section_course_id'   => $this->course->get_id(),
			'section_description' => '',
			'section_name'        => $section_name,
			'items'               => array(),
		);

		$section = $this->section_curd->create( $args );

		$this->result = array(
			'temp_id'     => $temp_id,
			'id'          => $section['section_id'],
			'items'       => $section['items'],
			'title'       => $section['section_name'],
			'description' => $section['section_description'],
			'course_id'   => $section['section_course_id'],
			'order'       => $section['section_order'],
		);

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function update_section_item( $args = array() ) {
		$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
		$item       = ! empty( $args['item'] ) ? $args['item'] : false;
		$item       = json_decode( wp_unslash( $item ), true );

		if ( ! ( $section_id && $item ) ) {
			return func_get_args();
		}

		// update lesson, quiz title
		$this->result = $this->section_curd->update_item( $item );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function remove_section_item( $args = array() ) {
		$section_id = $args['section_id'] ?? 0;
		$item_id    = $args['item_id'] ?? 0;

		try {
			// Instructor only remove item in my item.
			if ( absint( get_post_field( 'post_author', $item_id ) ) !== absint( get_current_user_id() ) && ! current_user_can( 'administrator' ) ) {
				throw new Exception( __( 'You can not delete this item!', 'learnpress' ) );
			}

			if ( ! ( $section_id && $item_id ) ) {
				throw new Exception( __( 'Invalid params!', 'learnpress' ) );
			}

			// remove item from course
			$this->course_curd->remove_item( $item_id );
			$this->result = true;
		} catch ( Throwable $e ) {
			$this->result = new WP_Error( '2', $e->getMessage() );
		}

		return $this->result;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function delete_section_item( $args = array() ) {
		$section_id = $args['section_id'] ?? 0;
		$item_id    = $args['item_id'] ?? 0;

		try {
			// Instructor only remove item in my item.
			if ( absint( get_post_field( 'post_author', $item_id ) ) !== absint( get_current_user_id() ) && ! current_user_can( 'administrator' ) ) {
				throw new Exception( __( 'You can not delete this item!', 'learnpress' ) );
			}

			if ( ! ( $section_id && $item_id ) ) {
				throw new Exception( __( 'Invalid params!', 'learnpress' ) );
			}

			$this->result = wp_trash_post( $item_id );
		} catch ( Throwable $e ) {
			$this->result = new WP_Error( '2', $e->getMessage() );
		}

		return $this->result;
	}

	/**
	 * @param array $args
	 *
	 * @return array|bool
	 */
	public function new_section_item( $args = array() ) {
		$section_id = $args['section_id'] ?? 0;
		$item       = $args['item'] ?? '';
		$item       = json_decode( wp_unslash( $item ), true );

		if ( ! ( $section_id && $item ) ) {
			return false;
		}

		// create new lesson, quiz and add to course
		$this->result = $this->section_curd->new_item( $section_id, $item );

		//$this->section_curd->update_final_item();

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function update_section_items( $args = array() ) {
		$section_id = $args['section_id'] ?? 0;
		$items      = $args['items'] ?? '';
		$items      = json_decode( wp_unslash( $items ), true );

		if ( ! ( $section_id && $items ) ) {
			return false;
		}

		$this->result = $this->section_curd->update_section_items( $section_id, $items );

		//$this->section_curd->update_final_item();

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function search_items( $args = array() ) {
		$query   = isset( $args['query'] ) ? $args['query'] : '';
		$type    = isset( $args['item_type'] ) ? $args['item_type'] : '';
		$page    = ! empty( $args['page'] ) ? $args['page'] : 1;
		$exclude = ! empty( $args['exclude'] ) ? $args['exclude'] : '';

		if ( $exclude ) {
			$exclude = json_decode( $exclude, true );
		}

		$ids_exclude = array();

		if ( is_array( $exclude ) ) {
			foreach ( $exclude as $item ) {
				$ids_exclude[] = $item['id'];
			}
		}

		$search = new LP_Modal_Search_Items(
			array(
				'type'       => $type,
				'context'    => 'course',
				'context_id' => $this->course->get_id(),
				'term'       => $query,
				'limit'      => apply_filters( 'learn-press/course-editor/choose-items-limit', 10 ),
				'paged'      => $page,
				'exclude'    => $ids_exclude,
			)
		);

		$id_items = $search->get_items();

		$items = array();
		foreach ( $id_items as $id ) {
			$post = get_post( $id );

			$items[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => $post->post_type,
			);
		}

		$this->result = array(
			'items'      => $items,
			'pagination' => $search->get_pagination( false ),
		);

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function add_items_to_section( $args = array() ) {
		$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
		$items      = ! empty( $args['items'] ) ? $args['items'] : false;
		$items      = json_decode( wp_unslash( $items ), true );

		if ( ! $items || ! $section_id ) {
			return false;
		}

		$this->result = $this->section_curd->add_items_section( $section_id, $items );

		return true;
	}
}
