<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Text_Attribute extends LP_Meta_Box_Attribute {
	/**
	 * Type field
	 *
	 * @var string
	 */
	public $type = 'lp_meta_box_text_input_field';

	/**
	 * Type input
	 *
	 * @var string
	 */
	public $type_input = 'text';

	/**
	 * Text placeholder
	 *
	 * @var string
	 */
	public $placeholder = '';

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
	 * @param string $type_input
	 * @param string $placeholder
	 */
	public function __construct(
		$id,
		$label = '',
		$description = '',
		$default = '',
		$desc_tip = '',
		$class = '',
		$style = '',
		$custom_attributes = array(),
		$type_input = 'text',
		$placeholder = ''
	) {
		$this->type_input  = $type_input;
		$this->placeholder = $placeholder;
		parent::__construct( $id, $label, $description, $this->type, $default, $desc_tip, $class, $style,
			$custom_attributes );
	}
}
