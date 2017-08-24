<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course_Item
 */
abstract class LP_Abstract_Course_Item extends LP_Abstract_Post_Data {

	/**
	 * The icon maybe used somewhere.
	 *
	 * @var string
	 */
	protected $_icon_class = '';

	/**
	 * The type of item.
	 *
	 * @var string
	 */
	protected $_item_type = '';

	/**
	 * @var LP_Course
	 */
	protected $_course = null;

	/**
	 * LP_Abstract_Course_Item constructor.
	 *
	 * @param $item mixed
	 * @param $args array
	 */
	public function __construct( $item, $args = null ) {
		parent::__construct( $item, $args );
	}

	/**
	 * @return string
	 */
	public function get_item_type() {
		return $this->_item_type;
	}

	/**
	 * @return string
	 */
	public function get_icon_class() {
		return $this->_icon_class;
	}

	/**
	 *
	 */
	public function is_preview() {
		return get_post_meta( $this->get_id(), '_lp_preview', true ) == 'yes';
	}

	/**
	 * Get the title of item.
	 *
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->get_id() );
	}

	/**
	 * Get the content of item.
	 *
	 * @return string
	 */
	public function get_content() {

		global $post;
		$post = get_post( $this->get_id() );
		setup_postdata( $post );

		ob_start();
		the_content();
		$content = ob_get_clean();

		wp_reset_postdata();

		return $content;
	}

	/**
	 * Return true if item can be shown in course curriculum.
	 *
	 * @return mixed
	 */
	public function is_visible() {
		/* section item display inside a section */
		$allow_items = learn_press_get_course_item_types();

		$item_type = get_post_type( $this->get_id() );

		// If item type does not allow
		$show = in_array( $item_type, $allow_items );

		return apply_filters( 'learn-press/course-item-visible', $show, $this->get_item_type(), $this->get_id() );
	}

	/**
	 * Get class of item.
	 *
	 * @param string $more
	 *
	 * @return array
	 */
	public function get_class( $more = '' ) {
		$defaults = array( 'course-item course-item-' . $this->get_item_type() );

		if ( is_array( $more ) ) {
			$defaults = array_merge( $defaults, $more );
		} else {
			$defaults[] = $more;
		}

		$classes = apply_filters( 'learn-press/course-item-class', $defaults, $this->get_item_type(), $this->get_id() );

		// Filter unwanted values
		$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );
		$classes = array_filter( $classes );
		$classes = array_unique( $classes );

		return $classes;
	}

	/**
	 * Get permalink of item inside course.
	 *
	 * @return string
	 */
	public function get_permalink() {
		$link = false;
		if ( $this->_course ) {
			$link = $this->_course->get_item_link( $this->get_id() );
		}

		return apply_filters( 'learn-press/course-item-link', $link, $this );
	}

	/**
	 * Set course parent of this item.
	 *
	 * @param LP_Course|int $course
	 */
	public function set_course( $course ) {
		if ( is_numeric( $course ) ) {
			$this->_course = learn_press_get_course( $course );
		} else {
			$this->_course = $course;
		}
	}

	/**
	 * Return course.
	 *
	 * @return LP_Course
	 */
	public function get_course() {
		return $this->_course;
	}

	/**
	 * To array.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		$post = get_post( $this->get_id() );

		return array(
			'id'    => $this->get_id(),
			'type'  => $this->get_item_type(),
			'title' => $post->post_title,
		);
	}
}