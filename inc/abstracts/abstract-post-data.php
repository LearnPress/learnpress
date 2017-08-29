<?php

/**
 * Class LP_Abstract_Post_Data
 */
class LP_Abstract_Post_Data extends LP_Abstract_Object_Data {
	/**
	 * LP_Abstract_Post_Data constructor.
	 *
	 * @param mixed $post
	 * @param array $args
	 */
	public function __construct( $post, $args = null ) {
		$id = 0;
		if ( is_numeric( $post ) ) {
			$id = absint( $post );
		} elseif ( $post instanceof LP_Abstract_Post_Data ) {
			$id = absint( $post->get_id() );
		} elseif ( isset( $post->ID ) ) {
			$id = absint( $post->ID );
		}

		settype( $args, 'array' );
		$args['id'] = $id;
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