<?php

/**
 * LP_Meta_Box_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Select_Attribute extends LP_Meta_Box_Attribute {
	/**
	 * Type of field
	 *
	 * @var string
	 */
	public $type = 'lp_meta_box_select_field';
	/**
	 * List value select
	 *
	 * @var array $options
	 */
	public $options;
	/**
	 * Type Multiple
	 *
	 * @var int $multiple 0|1
	 */
	public $multiple;

	/**
	 * LP_Meta_Box_Attribute constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param string $type
	 * @param mixed  $default
	 * @param mixed  $desc_tip
	 * @param mixed  $class
	 * @param mixed  $style
	 * @param array  $custom_attributes
	 * @param array  $options
	 * @param array  $options
	 * @param int    $multiple
	 */
	public function __construct(
		$id = '',
		$label = '',
		$description = '',
		$default = '',
		$desc_tip = '',
		$class = '',
		$style = '',
		$custom_attributes = array(),
		$options = array(),
		$multiple = 0
	) {
		$this->options  = $options;
		$this->multiple = $multiple;
		parent::__construct( $id, $label, $description, $this->type, $default, $desc_tip, $class, $style,
			$custom_attributes );

		return $this;
	}

	/**
	 * Set type select is multiple
	 *
	 * @param int $multiple .
	 */
	public function set_multiple( int $multiple ) {
		$this->multiple = $multiple;
	}
}
