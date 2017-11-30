<?php
/**
 * Class LP_Abstract_Post_Data.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Abstract_Post_Data' ) ) {

	/**
	 * Class LP_Abstract_Post_Data
	 */
	class LP_Abstract_Post_Data extends LP_Abstract_Object_Data {

		/**
		 * @var string
		 */
		protected $_post_type = '';

		/**
		 * @var string
		 */
		protected $_content = '';

		/**
		 * LP_Abstract_Post_Data constructor.
		 *
		 * @since 3.0.0
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
		 * @since 3.0.0
		 *
		 * @return array|mixed
		 */
		public function get_status() {
			return $this->get_data( 'status' );
		}

		/**
		 * Check if the post of this instance is exists.
		 *
		 * @since 3.0.0
		 *
		 * @return bool
		 */
		public function is_exists() {
			return get_post_type( $this->get_id() ) === $this->_post_type;
		}

		/**
		 * Check if the post in trash.
		 *
		 * @since 3.0.0
		 *
		 * @return bool
		 */
		public function is_trashed() {
			return get_post_status( $this->get_id() ) === 'trash';
		}

		/**
		 * Check if the post is publish.
		 *
		 * @since 3.0.0
		 *
		 * @return mixed
		 */
		public function is_publish() {
			return apply_filters( 'learn-press/' . $this->_post_type . '/is-publish', get_post_status( $this->get_id() ) === 'publish' );
		}

		/**
		 * Get the title.
		 *
		 * @since 3.0.0
		 *
		 * @param string $context
		 *
		 * @return string
		 */
		public function get_title( $context = '' ) {
			$title = get_the_title( $this->get_id() );

			if ( 'display' === $context ) {
				$title = do_shortcode( $title );
			}

			return $title;
		}

		/**
		 * Get the content.
		 *
		 * @since 3.0.0
		 *
		 * @return string
		 */
		public function get_content() {
			if ( ! $this->_content ) {

				global $post, $wp_query;

				$posts = apply_filters_ref_array( 'the_posts', array(
					array( get_post( $this->get_id() ) ),
					&$wp_query
				) );

				if ( $posts ) {
					$post = $posts[0];
				}

				setup_postdata( $post );
				ob_start();
				the_content();
				$this->_content = ob_get_clean();
				wp_reset_postdata();
			}

			return $this->_content;
		}

		/*
		 * Get post status.
		 *
		 * @since 3.0.0
		 */
		public function get_post_status() {
			return get_post_status( $this->get_id() );
		}

		/**
		 * Get post type.
		 *
		 * @since 3.0.0
		 *
		 * @return false|string
		 */
		public function get_post_type() {
			return get_post_type( $this->get_id() );
		}

		/**
		 * Get default post meta.
		 *
		 * @since 3.0.0
		 *
		 * @return array
		 */
		public static function get_default_meta() {
			return array();
		}
	}

}