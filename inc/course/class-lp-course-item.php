<?php
/**
 * Class LP_Course_Item.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course_Item' ) ) {
	/**
	 * Class LP_Course_Item.
	 */
	class LP_Course_Item extends LP_Abstract_Post_Data {

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
		protected $_preview = false;


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
		 * Get type of item.
		 *
		 * @param string $context
		 *
		 * @return string
		 */
		public function get_item_type( $context = '' ) {
			$post_type = $this->_item_type;

			if ( $context === 'display' ) {
				$post_type_object = get_post_type_object( $post_type );

				if ( $post_type_object ) {
					$post_type = $post_type_object->labels->singular_name;
				}
			}

			return $post_type;
		}

		/**
		 * @return string
		 */
		public function get_icon_class() {
			return $this->_icon_class;
		}

		/**
		 * Check lesson is preview
		 *
		 * @param string $context
		 *
		 * @return bool
		 */
		public function is_preview( $context = 'display' ): bool {
			$item_id = $this->get_id();

			$this->_preview = false;

			if ( $item_id && LP_LESSON_CPT === $this->get_post_type() ) {
				$is_lesson_preview = get_post_meta( $item_id, '_lp_preview', true );

				$this->_preview = 'yes' === $is_lesson_preview;
			}

			return apply_filters( 'learnpress/course/item-preview', $this->_preview, $this->get_id() );
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
		 * Get format of Post
		 *
		 * @return bool|false|mixed|string
		 */
		public function get_format() {
			$format = get_post_meta( $this->get_id(), 'post_format', true );

			if ( ! $format ) {
				$format = 'standard';
			}

			return $format;
		}

		/**
		 * Return true if item can be shown in course curriculum.
		 *
		 * @return mixed
		 * @deprecated 4.0.0
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
		 * @deprecated 4.2.3
		 */
		public function get_class( $more = '', $user_id = 0 ) {
			_deprecated_function( __METHOD__, '4.2.3', 'LP_Course_Item::get_class_v2' );
			return;
			$course_id = get_the_ID();

			if ( empty( $GLOBALS['get_class'] ) ) {
				$GLOBALS['get_class'] = 0;
			}

			$user_id = $user_id ? $user_id : get_current_user_id();
			$t       = microtime( true );
			$classes = LP_Object_Cache::get( 'item-' . $user_id . '-' . $this->get_id(), 'learn-press/post-classes' );

			if ( false === $classes ) {
				$curd      = new LP_User_Item_CURD();
				$all_items = $curd->parse_items_classes( $course_id, $user_id, $more );

				$classes = ! empty( $all_items[ $this->get_id() ] ) ? $all_items[ $this->get_id() ] : $defaults = array(
					'course-item',
					'course-item-' . $this->get_item_type(),
					'course-item-' . $this->get_id(),
				);
			}

			$GLOBALS['get_class'] += microtime( true ) - $t;

			return apply_filters(
				'learn-press/course-item-class-cached',
				$classes,
				$this->get_item_type(),
				$this->get_id(),
				$course_id
			);
		}

		/**
		 * Get class of item.
		 *
		 * @param $course_id
		 * @param $item_id
		 * @param $can_view_item
		 * @param $more
		 *
		 * @return array|mixed|null
		 */
		public function get_class_v2( $course_id, $item_id, $can_view_item, $more = array() ) {
			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				return $more;
			}

			$defaults = array_merge(
				array(
					'course-item',
					'course-item-' . $this->get_item_type(),
					'course-item-' . $item_id,
				),
				(array) $more
			);

			$user = learn_press_get_current_user();
			if ( ! $user ) {
				return $defaults;
			}

			$is_free            = $course->is_free();
			$enrolled           = $user->has_enrolled_or_finished( $course_id );
			$no_required_enroll = $course->is_no_required_enroll();

			$post_format = $this->get_format();
			if ( 'standard' !== $post_format && $post_format ) {
				$defaults[] = 'course-item-type-' . $post_format;
			}

			if ( $no_required_enroll ) {
				$defaults[] = 'item-free';
			} elseif ( ! $enrolled ) {
				$defaults['item-locked'] = 'item-locked';

				if ( $this->is_preview() ) {
					$defaults['item-preview'] = 'item-preview';
					$defaults['has-status']   = 'has-status';
					unset( $defaults['item-locked'] );
				}
			} elseif ( ! $can_view_item->flag ) {
				$defaults[] = 'item-locked';
			} else {
				$item_status = $user->get_item_status( $item_id, $course_id );
				$item_grade  = $user->get_item_grade( $item_id, $course_id );

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
						if ( $this->is_preview() ) {
							$defaults['item-preview'] = 'item-preview';
							$defaults['has-status']   = 'has-status';
						}

						$item_class = apply_filters(
							'learn-press/course-item-status-class',
							$item_status,
							$item_grade,
							$this->get_item_type(),
							$item_id,
							$course_id
						);

						if ( $item_class ) {
							$defaults[] = $item_class;
						}
				}
			}

			$classes = apply_filters(
				'learn-press/course-item-class',
				$defaults,
				$this->get_item_type(),
				$item_id,
				$course_id
			);

			// Filter unwanted values.
			$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );
			$classes = array_filter( $classes );
			$classes = array_unique( $classes );

			return $classes;
		}

		public function get_status_title() {
			$course_id      = get_the_ID();
			$status_message = '';

			$user = learn_press_get_current_user();
			if ( $user->get_item_status( $this->get_id(), $course_id ) === 'completed' ) {
				$item_grade = $user->get_item_grade( $this->get_id(), $course_id );

				if ( $item_grade === 'failed' ) {
					$status_message = _x( 'Failed', 'course item status title', 'learnpress' );
				} elseif ( $item_grade === 'passed' ) {
					$status_message = _x( 'Passed', 'course item status title', 'learnpress' );
				} else {
					$status_message = _x( 'Completed', 'course item status title', 'learnpress' );
				}
			} else {
				$status_message = _x( 'Unread', 'course item status title', 'learnpress' );
			}

			return apply_filters(
				'learn-press/course-item-status-title',
				$status_message,
				$this->get_id(),
				$course_id
			);
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

		public function get_course_id() {
			$course = $this->get_course();

			if ( $course ) {
				return $course->get_id();
			}

			return false;
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
		 * @param int    $item_id Item id.
		 * @param int    $course_id .
		 *
		 * @return LP_Course_Item|false
		 * @Todo: tungnx - review - rewrite - set cache - check where call this function
		 * @editor tungnx
		 * @modify 4.1.3 - change cache
		 * @version 4.0.2
		 * @since 3.x.x
		 */
		public static function get_item( $item_id = 0, $course_id = 0 ) {
			/*
			$lp_course_cache = LP_Course_Cache::instance();
			$key_cache       = sprintf( '%d/item_id/%d', $course_id, $item_id );
			$item            = $lp_course_cache->get_cache( $key_cache );*/

			$item = false;

			if ( false === $item ) {
				$item_post = get_post( $item_id );

				if ( ! $item_post ) {
					return false;
				}

				$item_type = $item_post->post_type;

				if ( learn_press_is_support_course_item_type( $item_type ) ) {
					$type = str_replace( 'lp_', '', $item_type );

					switch ( $type ) {
						case 'lesson':
							$item = LP_Lesson::get_lesson( $item_id );
							break;
						case 'quiz':
							$item = LP_Quiz::get_quiz( $item_id );
							break;
						default:
							$class_name = apply_filters(
								'learn-press/course-item-object-class',
								array(),
								$type,
								$item_type,
								$item_id
							);

							if ( ! empty( $class_name ) && isset( $class_name[ $type ] ) ) {
								$class = $class_name[ $type ];

								if ( is_string( $class ) && class_exists( $class ) ) {
									$item = new $class( $item_id );
								} elseif ( $class instanceof LP_Course_Item ) {
									$item = $class;
								}
							}

							break;
					}

					// Todo: don't know why when set course here, theme ivy still null course
					/*if ( $item instanceof LP_Course_Item ) {
						$item->set_course( $course_id );
					}*/

					// $lp_course_cache->set_cache( $key_cache, $item );
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

			return apply_filters(
				'learn-press/section-item-template',
				'item-' . str_replace( 'lp_', '', $item_type ),
				$item_type
			);
		}

		/**
		 * To array.
		 *
		 * @return array
		 * @since 3.0.0
		 */
		public function to_array() {
			$post = get_post( $this->get_id() );

			return apply_filters(
				'learn-press/item/to_array',
				array(
					'id'      => $this->get_id(),
					'type'    => $this->get_item_type(),
					'title'   => $post->post_title,
					'preview' => $this->is_preview(),
				)
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
			if ( ! $course_id && $this->get_course() ) {
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
		 * @deprecated 4.1.7.2
		 */
		/*public function is_viewing_question( $question_id = 0 ) {
			global $lp_quiz_question;
			if ( $question_id ) {
				$viewing = $lp_quiz_question && $lp_quiz_question->get_id() == $question_id;
			} else {
				$viewing = $lp_quiz_question ? $lp_quiz_question->get_id() : false;
			}

			return apply_filters( 'learn-press/quiz/is-viewing-question', $viewing, $question_id, $this->get_id() );
		}*/

		/**
		 * @param int $user_id
		 * @param int $course_id
		 *
		 * @return mixed
		 * @deprecated 4.1.7.2
		 */
		/*public function get_status_classes( $user_id = 0, $course_id = 0 ) {
			$status_classes = array();
			$course         = learn_press_get_course( $course_id );
			$user           = learn_press_get_user( $user_id, ! $user_id );

			if ( $course ) {
				if ( $this->is_preview() ) {
					$status_classes[] = 'item-preview';
				} elseif ( $course->is_free() && $course->is_no_required_enroll() ) {
					$status_classes[] = 'item-free';
				}
			}

			if ( $user ) {
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

			return apply_filters(
				'learn-press/item-status-classes',
				$status_classes,
				$this->get_id(),
				$course_id,
				$user_id
			);
		}*/

		/**
		 * Get duration of quiz
		 *
		 * @return LP_Duration
		 */
		public function get_duration() {
			$duration = $this->get_data( 'duration' );

			if ( false === $duration || '' === $duration ) {
				$duration = get_post_meta( $this->get_id(), '_lp_duration', true );

				if ( $duration ) {
					$duration = new LP_Duration( $duration );
				} else {
					$duration = new LP_Duration( 0 );
				}

				$this->_set_data( 'duration', $duration );
			}

			return apply_filters( 'learn-press/course-item-duration', $duration, $this->get_id() );
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
