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
	 * To array.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		$post = get_post( $this->get_id(), ARRAY_A );

		return array(
			'id'   => $this->get_id(),
			'type' => $this->get_item_type(),
			'post' => $post,
		);
	}
}