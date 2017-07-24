<?php

/**
 * Class LP_Abstract_Post_Data
 */
class LP_Abstract_Post_Data extends LP_Abstract_Object_Data {
	/**
	 * LP_Abstract_Post_Data constructor.
	 *
	 * @param mixed $post_id
	 * @param array $args
	 */
	public function __construct( $post_id, $args = null ) {
		settype( $args, 'array' );
		$args['id'] = $post_id;
		parent::__construct( $args );
	}

	/**
	 * Get post meta.
	 *
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function get_meta( $key, $single = true ) {
		return get_post_meta( $this->get_id(), $key, $single );
	}

	/**
	 * Update post meta.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param mixed  $prev
	 *
	 * @return bool|int
	 */
	public function update_meta( $key, $value, $prev = '' ) {
		return update_post_meta( $this->get_id(), $key, $value, $prev );
	}
}