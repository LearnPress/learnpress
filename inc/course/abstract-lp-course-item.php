<<<<<<< HEAD
<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course_Item
 */
abstract class LP_Abstract_Course_Item {
	/**
	 * @var null
	 */
	protected $_item = null;

	/**
	 * LP_Abstract_Course_Item constructor.
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		$this->_item = $item;
		$this->id    = $this->_item->ID;
	}

	/**
	 *
	 */
	public function is_preview() {
		return get_post_meta( $this->id, '_lp_preview', true ) == 'yes';
	}
=======
<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course_Item
 */
abstract class LP_Abstract_Course_Item {
	/**
	 * @var null
	 */
	protected $_item = null;

	/**
	 * LP_Abstract_Course_Item constructor.
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		$this->_item = $item;
		$this->id    = $this->_item->ID;
	}

	/**
	 *
	 */
	public function is_preview() {
		return get_post_meta( $this->id, '_lp_preview', true ) == 'yes';
	}
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
}