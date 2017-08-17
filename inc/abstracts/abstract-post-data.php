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
	 * Get status of post.
	 *
	 * @return array|mixed
	 */
	public function get_status() {
		return $this->get_data( 'status' );
	}
}