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
		 * @var null
		 */
		protected $_video = null;

		/**
		 * LP_Abstract_Post_Data constructor.
		 *
		 * @param mixed $post
		 * @param array $args
		 *
		 * @since 3.0.0
		 *
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
		 * @since 3.0.0
		 *
		 */
		public function get_status() {
			return $this->get_data( 'status' );
		}

		/**
		 * Check if the post of this instance is exists.
		 *
		 * @return bool
		 * @since 3.0.0
		 *
		 */
		public function is_exists() {
			return get_post_type( $this->get_id() ) === $this->_post_type;
		}

		/**
		 * Check if the post in trash.
		 *
		 * @return bool
		 * @since 3.0.0
		 *
		 */
		public function is_trashed() {
			return get_post_status( $this->get_id() ) === 'trash';
		}

		/**
		 * Check if the post is publish.
		 *
		 * @return mixed
		 * @since 3.0.0
		 *
		 */
		public function is_publish() {
			return apply_filters( 'learn-press/' . $this->_post_type . '/is-publish',
				get_post_status( $this->get_id() ) === 'publish' );
		}

		/**
		 * Get the title.
		 *
		 * @param string $context
		 *
		 * @return string
		 * @since 3.0.0
		 *
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
		 * @param string $context
		 * @param int $length
		 * @param string $more
		 *
		 * @return string
		 * @since 3.0.0
		 *
		 */
		public function get_content( $context = 'display', $length = - 1, $more = '' ) {
			if ( 'display' === $context ) {
				global $post, $wp_query;

				$posts = apply_filters_ref_array( 'the_posts', array(
					array( get_post( $this->get_id() ) ),
					&$wp_query
				) );

				if ( $posts ) {
					$post = $posts[0];
				}

				setup_postdata( $post );
				$content_post   = get_the_content();
				$content_post   = apply_filters( 'the_content', $content_post );
				$content_post   = str_replace( ']]>', ']]&gt;', $content_post );
				$this->_content = $content_post;
				wp_reset_postdata();
			} else {
				$content = get_post_field( 'post_content', $this->get_id() );
				if ( $length > 1 ) {
					$content = wp_trim_words( $content, $length, $more );
				}

				return $content;
			}

			return $this->_content;
		}

		public function get_video() {

			if ( 'yes' !== LP()->settings->get( 'enable_lesson_video' ) ) {
				return false;
			}

			if ( ( $content = $this->get_content() ) && ( $this->_video === null ) ) {
				$video = get_media_embedded_in_content( $content, array( 'video', 'object', 'embed', 'iframe' ) );

				if ( $video ) {
					$this->_video = $video;
				} else {
					$this->_video = '';
				}
			}

			return $this->_video;
		}

		public function get_content_video() {
			$content = $this->get_content();

			if ( $this->get_video() ) {
				return str_replace( $this->_video[0], '', $content );
			}

			return $content;
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
		 * @return false|string
		 * @since 3.0.0
		 *
		 */
		public function get_post_type() {
			return get_post_type( $this->get_id() );
		}

		/**
		 * Get default post meta.
		 *
		 * @return array
		 * @since 3.0.0
		 *
		 */
		public static function get_default_meta() {
			return array();
		}

		public function get_edit_link() {
			return get_edit_post_link( $this->get_id() );
		}

		public function current_user_can_edit() {
			return learn_press_get_current_user()->can_edit( $this->get_id() );
		}

		/**
		 * Get post meta data.
		 * Check if the meta is not stored on database then return FALSE
		 *
		 * @updated 3.1.0
		 *
		 * @param string $key
		 * @param bool $single
		 *
		 * @return mixed
		 */
		public function get_meta( $key, $single = true ) {
			if ( ! metadata_exists( 'post', $this->get_id(), $key ) ) {
				return false;
			}

			return get_post_meta( $this->get_id(), $key, $single );
		}
	}
}
