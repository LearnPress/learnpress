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
	 * List value select
	 *
	 * @var array $options
	 */
	public $options;

	/**
	 * LP_Meta_Box_Attribute constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param string $type
	 * @param mixed $default
	 * @param mixed $desc_tip
	 * @param mixed $class
	 * @param mixed $style
	 * @param array $custom_attributes
	 * @param array $options
	 */
	public function __construct(
		$id = '',
		$label = '',
		$description = '',
		$type = '',
		$default = '',
		$desc_tip = '',
		$class = '',
		$style = '',
		$custom_attributes = array(),
		$options = array()
	) {
		$this->options = $options;
		parent::__construct( $id, $label, $description, $type, $default, $desc_tip, $class, $style,
			$custom_attributes );
	}
}
