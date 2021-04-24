<?php

/**
 * LP_Meta_Box_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Field {
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
	 * Value default of field
	 *
	 * @var mixed
	 */
	public $default;

	/**
	 * Extra array.
	 *
	 * @var string $class
	 */
	public $extra = array();

	/**
	 * LP_Meta_Box_Attribute constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed  $default
	 * @param mixed  $desc_tip
	 * @param mixed  $class
	 * @param mixed  $style
	 * @param array  $custom_attributes
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		$this->label       = $label;
		$this->description = $description;
		$this->default     = $default;
		$this->extra       = $extra;
	}

	public function meta_value( $thepostid ) {
		return get_post_meta( $thepostid, $this->id, true );
	}

	public function output( $thepostid ) {
	}

	public function save( $post_id ) {
	}
}
