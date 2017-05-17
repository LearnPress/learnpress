<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course_Item
 */
abstract class LP_Abstract_Course_Item extends LP_Abstract_Object {

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
	 *
	 */
	public function is_preview() {
		return get_post_meta( $this->get_id(), '_lp_preview', true ) == 'yes';
	}
}