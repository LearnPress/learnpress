<?php

/**
 * LP_Meta_Box_Field
 *
 * @author nhamdv
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
	 * Condition logic show or hide when checkbox, select or...
	 */
	public $condition = false;

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

		$show = ! empty( $extra['show'] ) ? htmlentities( wp_json_encode( $extra['show'] ) ) : false;
		$hide = ! empty( $extra['hide'] ) ? htmlentities( wp_json_encode( $extra['hide'] ) ) : false;

		if ( $show ) {
			$this->condition = 'data-show="' . $show . '"';
		} elseif ( $hide ) {
			$this->condition = 'data-hide="' . $hide . '"';
		}
	}

	public function meta_value( $thepostid ) {
		return get_post_meta( $thepostid, $this->id, true );
	}

	public function output( $thepostid ) {
		return '';
	}

	public function save( $post_id ) {
	}
}
