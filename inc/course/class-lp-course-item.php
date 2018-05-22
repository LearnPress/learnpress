<?php
/**
 * Class LP_Course_Item.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course_Item' ) ) {
	/**
	 * Class LP_Course_Item.
	 */
	class LP_Course_Item extends LP_Abstract_Post_Data implements ArrayAccess {

		/**
		 * The icon maybe used somewhere.
		 *
		 * @var string
		 */
		protected $_icon_class = '';

		/**
		 * The type of item.
		 *
		 * @var string
		 */
		protected $_item_type = '';

		/**
		 * @var LP_Course
		 */
		protected $_course = null;

		/**
		 * @var LP_Course_Section
		 */
		protected $_section = null;

		/**
		 * @var null
		 */
		protected $_content = null;

		/**
		 * @var bool
		 */
		protected $_preview = '';

		/**
		 * LP_Course_Item constructor.
		 *
		 * @param $item mixed
		 * @param $args array
		 */
		public function __construct( $item, $args = null ) {
			parent::__construct( $item, $args );
			$this->add_support( 'comments', get_post_field( 'comment_status', $this->get_id() ) === 'open' );
		}

		/**
		 * @return string
		 */
		public function get_item_type() {
			return $this->_item_type;
		}

		/**
		 * @return string
		 */
		public function get_icon_class() {
			return $this->_icon_class;
		}

		/**
		 * @param string $context
		 *
		 * @return bool
		 */
		public function is_preview( $context = 'display' ) {
			if ( '' === $this->_preview ) {
				$is_preview = get_post_meta( $this->get_id(), '_lp_preview', true ) == 'yes';

				if ( $course = $this->get_course() ) {
					$user_id = get_current_user_id();

					if ( false === ( $cached = wp_cache_get( 'item-' . $user_id . '-' . $course->get_id() . '-' . $this->get_id(), 'lp-preview-items' ) ) ) {
						$user = learn_press_get_current_user();

						if ( $user->has_enrolled_course( $course->get_id() ) ) {
							$is_preview = false;
						}
						wp_cache_set( 'item-' . $user_id . '-' . $course->get_id() . '-' . $this->get_id(), $is_preview ? 'yes' : 'no', 'lp-preview-items' );
					} else {
						$is_preview = $cached === 'yes' ? true : false;
					}
				}
				$this->_preview = $is_preview;
			}

			return $context === 'display' ? apply_filters( 'learn-press/course-item-preview', $this->_preview, $this->get_id() ) : $this->_preview;
		}

		/**
		 * @param string $context
		 *
		 * @return string
		 */
		public function get_title( $context = '' ) {
			return apply_filters( 'learn-press/course-item-title', parent::get_title( $context ), $this->get_id() );
		}

		/**
		 * @return bool|false|mixed|string
		 */
		public function get_format() {
			$format = ( false !== ( $format = wp_cache_get( 'item-format-' . $this->get_id(), 'lp-item-formats' ) ) ) ? $format : get_post_format( $this->get_id() );

			if ( ! $format ) {
				$format = 'standard';
			}

			return $format;
		}

		/**
		 * Return true if item can be shown in course curriculum.
		 *
		 * @return mixed
		 */
		public function is_visible() {
			$show = true;

			return apply_filters( 'learn-press/course-item-visible', $show, $this->get_item_type(), $this->get_id() );
		}

		/**
		 * Get class of item.
		 *
		 * @param string $more
		 * @param int    $user_id
		 *
		 * @return array
		 */
		public function get_class( $more = '', $user_id = 0 ) {
			global $lp_course_item;

			$course_id = get_the_ID();

			if ( false === ( $classes = wp_cache_get( 'item-' . $user_id . '-' . $this->get_id(), 'lp-post-classes' ) ) ) {
				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}

				$defaults = array(
					'course-item',
					'course-item-' . $this->get_item_type(),
					'course-item-' . $this->get_id()
				);

				if ( ( 'standard' !== ( $post_format = $this->get_format() ) ) && $post_format ) {
					$defaults[] = 'course-item-type-' . $post_format;
				}

				if ( $lp_course_item && $lp_course_item->get_id() == $this->get_id() ) {
					$defaults[] = 'current';
				}

				if ( is_array( $more ) ) {
					$defaults = array_merge( $defaults, $more );
				} else {
					$defaults[] = $more;
				}
				$course_id = 0;
				$enrolled  = false;

				if ( $this->is_preview() ) {
					$defaults[] = 'item-preview';
					$defaults[] = 'has-status';
				} elseif ( $this->is_blocked() ) {
					$defaults[] = 'item-locked';
				} else {
					if ( $course = $this->get_course() ) {
						$course_id = $course->get_id();
						if ( $course->is_free() && ! $course->is_required_enroll() ) {
							$defaults[] = 'item-free';
						} else {
							if ( $user = learn_press_get_user( $user_id, ! $user_id ) ) {
								if ( $enrolled = $user->has_enrolled_course( $course_id ) ) {
									$item_status = $user->get_item_status( $this->get_id(), $course_id );
									$item_grade  = $user->get_item_grade( $this->get_id(), $course_id );

									if ( $item_status ) {
										$defaults[] = 'has-status';
										$defaults[] = 'status-' . $item_status;
									}
									switch ( $item_status ) {
										case 'started':
											break;
										case 'completed':
											$defaults[] = $item_grade;
											break;
										default:
											if ( $item_class = apply_filters( 'learn-press/course-item-status-class', $item_status, $item_grade, $this->get_item_type(), $this->get_id(), $course_id ) ) {
												$defaults[] = $item_class;
											}
									}
								}
							}
							if ( ! $enrolled ) {
								$defaults[] = 'item-locked';
							}
						}
					} else {
						$defaults[] = 'item-locked';
					}
				}
				$classes = apply_filters( 'learn-press/course-item-class', $defaults, $this->get_item_type(), $this->get_id(), $course_id );

				// Filter unwanted values
				$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );
				$classes = array_filter( $classes );
				$classes = array_unique( $classes );

				wp_cache_set( 'item-' . $user_id . '-' . $this->get_id(), $classes, 'lp-post-classes' );
			}

			return apply_filters( 'learn-press/course-item-class-cached', $classes, $this->get_item_type(), $this->get_id(), $course_id );
		}

		/**
		 * Get permalink of item inside course.
		 *
		 * @return string
		 */
		public function get_permalink() {
			$link = false;
			if ( $this->_course ) {
				$link = $this->_course->get_item_link( $this->get_id() );
			}

			return apply_filters( 'learn-press/course-item-link', $link, $this );
		}

		/**
		 * Set course parent of this item.
		 *
		 * @param LP_Course|LP_Abstract_Course|int $course
		 */
		public function set_course( $course ) {
			if ( is_numeric( $course ) ) {
				$this->_course = learn_press_get_course( $course );
			} else {
				$this->_course = $course;
			}
		}

		/**
		 * Return course.
		 *
		 * @return LP_Course
		 */
		public function get_course() {
			return $this->_course;
		}

		/**
		 * @param LP_Course_Section $section
		 */
		public function set_section( $section ) {
			$this->_section = $section;
		}

		/**
		 * @return LP_Course_Section
		 */
		public function get_section() {
			return $this->_section;
		}

		/**
		 * Get instance of an item from post
		 *
		 * @param WP_Post|int                      $post
		 * @param LP_Course|LP_Abstract_Course|int $course
		 *
		 * @return LP_Course_Item
		 */
		public static function get_item( $post, $course = null ) {
			$item_type = '';
			$item_id   = 0;

			if ( is_numeric( $post ) && $post > 0 ) {
				$post = get_post( $post );
			}

			if ( isset( $post->ID ) ) {
				$item_type = $post->post_type;
				$item_id   = $post->ID;
			}

			if ( false === ( $item = wp_cache_get( $item_id, 'lp-object-classes' ) ) ) {

				if ( $item_type ) {
					if ( learn_press_is_support_course_item_type( $item_type ) ) {
						$type = str_replace( 'lp_', '', $item_type );

						$class = apply_filters( 'learn-press/course-item-object-class', false, $type, $item_type, $item_id );

						if ( is_string( $class ) && class_exists( $class ) ) {
							$item = new $class( $post->ID, $post );
						} elseif ( $class instanceof LP_Course_Item ) {
							$item = $class;
						}

						if ( ! $item ) {

							switch ( $type ) {
								case 'lesson':
									$item = LP_Lesson::get_lesson( $item_id );
									break;
								case 'quiz':
									$item = LP_Quiz::get_quiz( $item_id );
									break;
							}
						}
					}
				}
				wp_cache_set( $item_id, $item, 'lp-object-classes' );

				if ( $course ) {
					$item->set_course( $course );
				}
			}

			return apply_filters( 'learn-press/get-course-item', $item, $item_type, $item_id );
		}

		/**
		 * Get template name of item.
		 *
		 * @return string
		 */
		public function get_template() {
			$item_type = $this->get_item_type();

			return apply_filters( 'learn-press/section-item-template', 'item-' . str_replace( 'lp_', '', $item_type ), $item_type );
		}

		/**
		 * To array.
		 *
		 * @since 3.0.0
		 *
		 * @return array
		 */
		public function to_array() {
			$post = get_post( $this->get_id() );

			return array(
				'id'      => $this->get_id(),
				'type'    => $this->get_item_type(),
				'title'   => $post->post_title,
				'preview' => $this->is_preview()
			);
		}

		/**
		 * Create nonce for checking actions on an item.
		 *
		 * @param string $action
		 * @param int    $course_id
		 * @param int    $user_id
		 *
		 * @return string
		 */
		public function create_nonce( $action = '', $course_id = 0, $user_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = $this->get_course()->get_id();
			}

			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$action = $this->get_nonce_action( $action, $course_id, $user_id );

			return wp_create_nonce( $action );
		}

		/**
		 * Verify nonce for an action on item.
		 *
		 * @param string $nonce
		 * @param string $action
		 * @param int    $course_id
		 * @param int    $user_id
		 *
		 * @return false|int
		 */
		public function verify_nonce( $nonce, $action = '', $course_id = 0, $user_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = $this->get_course()->get_id();
			}

			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$action = $this->get_nonce_action( $action, $course_id, $user_id );

			return wp_verify_nonce( $nonce, $action );
		}

		/**
		 * @param $action
		 * @param $course_id
		 * @param $user_id
		 *
		 * @return string
		 */
		public function get_nonce_action( $action, $course_id, $user_id ) {
			return sprintf( '%s-item-%d-%d-%d', $action, $user_id, $course_id, $this->get_id() );
		}

		/**
		 * @param int $question_id
		 *
		 * @return mixed
		 */
		public function is_viewing_question( $question_id = 0 ) {
			global $lp_quiz_question;
			if ( $question_id ) {
				$viewing = $lp_quiz_question && $lp_quiz_question->get_id() == $question_id;
			} else {
				$viewing = $lp_quiz_question ? $lp_quiz_question->get_id() : false;
			}

			return apply_filters( 'learn-press/quiz/is-viewing-question', $viewing, $question_id, $this->get_id() );
		}

		/**
		 * @param int $user_id
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_status_classes( $user_id = 0, $course_id = 0 ) {
			$status_classes = array();
			if ( $course = learn_press_get_course( $course_id ) ) {
				if ( $this->is_preview() ) {
					$status_classes[] = 'item-preview';
				} elseif ( $course->is_free() && ! $course->is_required_enroll() ) {
					$status_classes[] = 'item-free';
				}
			}

			if ( $user = learn_press_get_user( $user_id, ! $user_id ) ) {
				$item_status = $user->get_item_status( $this->get_id(), $course_id );
				$item_grade  = $user->get_item_grade( $this->get_id(), $course_id );

				if ( $item_status ) {
					$status_classes[] = 'course-item-status';
					$status_classes[] = 'item-' . $item_status;
				}
				switch ( $item_status ) {
					case 'started':
						break;
					case 'completed':
						$status_classes[] = $item_grade;
				}
			}

			return apply_filters( 'learn-press/item-status-classes', $status_classes, $this->get_id(), $course_id, $user_id );
		}

		/**
		 * Get duration of quiz
		 *
		 * @return LP_Duration
		 */
		public function get_duration() {
			$duration = $this->get_data( 'duration' );
			if ( false === $duration || '' === $duration ) {
				if ( $duration = get_post_meta( $this->get_id(), '_lp_duration', true ) ) {
					$duration = new LP_Duration( $duration );
				} else {
					$duration = new LP_Duration( 0 );
				}

				$this->_set_data( 'duration', $duration );
			}

			return apply_filters( 'learn-press/course-item-duration', $duration, $this->get_id() );
		}

		/**
		 * @param int $course_id
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function is_blocked( $course_id = 0, $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$key = 'course-item-' . $user_id . '-' . $course_id;

			if ( false === ( $blocked_items = wp_cache_get( $key, 'blocked-items' ) ) ) {
				$blocked_items = $this->_parse_item_block_status( $course_id, $user_id, $key );
			}

			$is_blocked = isset( $blocked_items[ $this->get_id() ] ) ? $blocked_items[ $this->get_id() ] : false;

			if ( false === $is_blocked ) {
				if ( $course_id ) {
					$course = learn_press_get_course( $course_id );
				} else {
					$course    = $this->get_course();
					$course_id = $course ? $course->get_id() : 0;
				}

				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}

				$user = learn_press_get_user( $user_id );

				if ( ! $course ) {
					$blocked = 'yes';
				} else if ( ! $course->is_required_enroll() || $this->is_preview() ) {
					$blocked = 'no';
				} else {
					if ( $user ) {
						$blocked = $this->_item_is_blocked( $user, $course, $user->get_course_data( $course_id ) );
					} else {
						$blocked = 'yes';
					}
				}

				if ( ! is_array( $blocked_items ) ) {
					$blocked_items = array();
				}
				$blocked_items[ $this->get_id() ] = $blocked;

				wp_cache_set( $key, $blocked_items, 'blocked-items' );
				$is_blocked = $blocked;
				//return apply_filters( 'learn-press/course-item/is-blocked', $blocked === 'yes' ? true : false, $this->get_id(), $course_id, $user_id );
			}

			return apply_filters( 'learn-press/course-item/is-blocked', $is_blocked === 'yes' ? true : false, $this->get_id(), $course_id, $user_id );
		}

		protected function _parse_item_block_status( $course_id, $user_id, $cache_key ) {
			if ( ! $course = learn_press_get_course( $course_id ) ) {
				return false;
			}

			$user             = learn_press_get_user( $user_id );
			$course_items     = $course->get_items();
			$course_item_data = $user->get_course_data( $course_id );

			if ( ! $course->is_required_enroll() ) {
				$blocked_items = array_fill_keys( $course_items, 'no' );
			} elseif ( ! $user || $user->is_guest() ) {
				$blocked_items = array_fill_keys( $course_items, 'yes' );
			} else {
				$blocked       = $this->_item_is_blocked( $user, $course, $course_item_data );
				$blocked_items = array_fill_keys( $course_items, $blocked );
			}
			$block_item_types = learn_press_get_block_course_item_types();

			foreach ( $course_items as $course_item ) {
				if ( $item = $course->get_item( $course_item ) ) {
					if ( $item->is_preview() ) {
						$blocked_items[ $course_item ] = 'no';
					} elseif ( ! $block_item_types || is_array( $block_item_types ) && ! in_array( $item->get_post_type(), $block_item_types ) ) {
						$blocked_items[ $course_item ] = 'no';
					}
				}
			}

			$blocked_items = apply_filters( 'learn-press/course-item/parse-block-statuses', $blocked_items, $course_id, $user_id );

			wp_cache_set( $cache_key, $blocked_items, 'blocked-items' );

			return $blocked_items;
		}

		/**
		 * @param LP_User             $user
		 * @param LP_Course           $course
		 * @param LP_User_Item_Course $course_item_data
		 *
		 * @return string
		 */
		protected function _item_is_blocked( $user, $course, $course_item_data ) {
			if ( $is_admin = in_array( 'administrator', $user->get_roles() ) ) {
				$blocked = 'no';
			} else if ( $user->has_course_status( $course->get_id(), array( 'enrolled', 'finished' ) ) ) {
				$blocked = 'no';

				if ( $course->is_block_item_content() && $course_item_data->get_finishing_type() !== 'click'/*$course_item_data->is_exceeded() < 0*/ ) {
					$blocked = 'yes';
				}
			} else {
				$blocked = 'yes';
			}

			return $blocked;
		}

		public function offsetExists( $offset ) {
		}

		public function offsetGet( $offset ) {
		}

		public function offsetSet( $offset, $value ) {
		}

		public function offsetUnset( $offset ) {
		}
	}
}