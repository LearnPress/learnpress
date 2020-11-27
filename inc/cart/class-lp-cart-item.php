<?php
/**
 * Class LP_Order_Item
 */
class LP_Order_Item {
	/**
	 * @var null
	 */
	protected $_item = null;

	public function __construct( $item ) {
		$this->_item = $item;
	}
}
