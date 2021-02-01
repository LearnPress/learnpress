<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Duration_Attribute extends LP_Meta_Box_Attribute {
	/**
	 * Key id of field
	 *
	 * @var string
	 */
	public $default_time = '';

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
	 * @param string $default_time
	 * @param array $custom_attributes
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
		$default_time = ''
	) {
		$this->default_time = $default_time;
		parent::__construct( $id, $label, $description, $type, $default, $desc_tip, $class, $style,
			$custom_attributes );
	}
}
