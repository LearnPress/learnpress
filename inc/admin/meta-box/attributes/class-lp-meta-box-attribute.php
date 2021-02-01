<?php

/**
 * LP_Meta_Box_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Attribute {
	/**
	 * Key id of field
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Label of field
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Description of field
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Tip description of field
	 *
	 * @var string
	 */
	public $desc_tip = '';

	/**
	 * Type of field
	 *
	 * @var string
	 */
	public $type = 'lp_meta_box_text_input_field';

	/**
	 * Value default of field
	 *
	 * @var mixed
	 */
	public $default;

	/**
	 * Class of field
	 *
	 * @var string $class
	 */
	public $class = '';

	/**
	 * Style of field
	 *
	 * @var string $class
	 */
	public $style = '';

	/**
	 * Style of field
	 *
	 * @var string $class
	 */
	public $custom_attributes = array();

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
		$custom_attributes = array()
	) {
		$this->id                = $id;
		$this->label             = $label;
		$this->description       = $description;
		$this->type              = $type;
		$this->default           = $default;
		$this->desc_tip          = $desc_tip;
		$this->class             = $class;
		$this->style             = $style;
		$this->custom_attributes = $custom_attributes;
	}
}
