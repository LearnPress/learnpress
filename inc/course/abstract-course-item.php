<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course_Item
 */
abstract class LP_Abstract_Course_Item extends LP_Abstract_Object_Data {

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
	public function __construct( $item, $args ) {
		parent::__construct( $args );
	}

	/**
	 * @return string
	 */
	public function get_item_type(){
		return $this->_item_type;
	}

	/**
	 * @return string
	 */
	public function get_icon_class(){
		return $this->_icon_class;
	}

	/**
	 *
	 */
	public function is_preview() {
		return get_post_meta( $this->get_id(), '_lp_preview', true ) == 'yes';
	}
}