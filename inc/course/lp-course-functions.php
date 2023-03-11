<?php
/**
 * Common functions to manipulate with course, lesson, quiz, questions, etc...
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

/**
 * Get course current on single course or course by id
 * Only use learn_press_get_course() on the single page
 * Another page use learn_press_get_course(id)
 *
 * @param int $the_course
 * @since 3.0.0
 * @version 1.0.1
 * @editor tungnx
 * @return bool|LP_Course|mixed
 */
function learn_press_get_course( $the_course = 0 ) {
	$the_course = (int) $the_course;

	if ( 0 === $the_course ) {
		$the_course = get_the_ID() ? get_the_ID() : 0;
	}

	return LP_Course::get_course( $the_course );
}

/**
 * @editor tungnx
 * @modify 4.1.4.1 - comment - not use
 */
/*function learn_press_get_course_by_id( $id ) {
	if ( false !== ( $courses = LP_Object_Cache::get( 'object', 'learn-press/courses' ) ) ) {
		return ! empty( $courses[ $id ] ) ? $courses[ $id ] : false;
	}

	return false;
}*/

/**
 * Create nonce for course action.
 * Return nonce created with format 'learn-press-$action-$course_id-course-$user_id'
 *
 * @param string $action [retake, purchase, enroll]
 * @param int $course_id
 * @param int $user_id
 *
 * @return string
 * @since 3.0.0
 * @deprecated 4.1.6.9
 */
/*function learn_press_create_course_action_nonce( $action, $course_id = 0, $user_id = 0 ) {
	return LP_Nonce_Helper::create_course( $action, $course_id, $user_id );
}*/

/**
 * Verify nonce for course action.
 *
 * @param string $nonce
 * @param string $action
 * @param int $course_id
 * @param int $user_id
 *
 * @return bool
 * @since 3.0.0
 * @deprecated 4.1.6.9
 */
/*function learn_press_verify_course_action_nonce( $nonce, $action, $course_id = 0, $user_id = 0 ) {
	return LP_Nonce_Helper::verify_course( $nonce, $action, $course_id, $user_id );
}*/

/**
 * Get type of items are supported in course curriculum (post types).
 * Default: [lp_lesson, lp_quiz]
 *
 * @return mixed
 * @since 3.0.0
 * @editor tungnx
 * @version  1.0.1
 * @return array
 */
function learn_press_get_course_item_types( bool $return_only_value = true ): array {
	return apply_filters(
		'learn-press/course-item-type',
		array( LP_LESSON_CPT, LP_QUIZ_CPT )
	);
}

/**
 * Get type of items can purchase on LP Order.
 * Default: ['lp_course', 'lp_certificate']
 *
 * @return mixed
 * @since 3.0.0
 *
 */
function learn_press_get_item_types_can_purchase() {
	return apply_filters(
		'learn-press/purchase/item-types/can-purchase',
		array( LP_COURSE_CPT )
	);
}

/**
 * Get the courses that a item is assigned to
 *
 * @param $item
 *
 * @return mixed
 * @Todo - tungnx need review code to rewrite.
 */
function learn_press_get_item_courses( $item ) {
	global $wpdb;
	$query = $wpdb->prepare(
		"
		SELECT c.*
		FROM {$wpdb->posts} c
			INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
			INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
			WHERE si.item_id = %d
		",
		$item
	);

	return $wpdb->get_results( $query );
}

function _learn_press_usort_terms_by_ID( $terms ) {
	$version = get_bloginfo( 'version' );
	if ( version_compare( $version, '4.7', '>=' ) ) {
		$terms = wp_list_sort( $terms, 'term_id' );
	} else {
		usort( $terms, '_usort_terms_by_ID' );
	}

	return $terms;
}

/*function learn_press_course_post_type_link( $permalink, $post ) {
	if ( $post->post_type !== 'lp_course' ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post
	$terms = get_the_terms( $post->ID, 'course_category' );

	if ( ! empty( $terms ) ) {
		$terms           = _learn_press_usort_terms_by_ID( $terms ); // order by ID
		$category_object = apply_filters(
			'learn_press_course_post_type_link_course_category',
			$terms[0],
			$terms,
			$post
		);
		$category_object = get_term( $category_object, 'course_category' );
		$course_category = $category_object->slug;

		if ( $parent = $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'course_category' );
				$course_category = $ancestor_object->slug . '/' . $course_category;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
		$course_category = _x( 'uncategorized', 'slug', 'learnpress' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%course_category%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$course_category,
		$course_category,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}*/

//add_filter( 'post_type_link', 'learn_press_course_post_type_link', 10, 2 );

function learn_press_item_meta_format( $item, $nonce = '' ) {
	if ( current_theme_supports( 'post-formats' ) ) {
		$format = get_post_format( $item );
		if ( false === $format ) {
			$format = 'standard';
		}

		// return false to hide post format
		$format = apply_filters( 'learn_press_course_item_format', $format, $item );
		if ( $format ) {
			printf(
				'<label for="post-format-0" class="post-format-icon post-format-%s" title="%s"></label>',
				$format,
				ucfirst( $format )
			);
		} else {
			echo esc_html( $nonce );
		}
	}
}

function learn_press_course_item_format_exclude( $format, $item ) {
	if ( learn_press_get_post_type( $item ) != LP_LESSON_CPT || ( $format == 'standard' ) ) {
		$format = false;
	}

	return $format;
}

/**
 * Get curriculum of a course
 *
 * @param $course_id
 *
 * @return mixed
 * @version 1.0
 *
 */
function learn_press_get_course_curriculum( $course_id ) {
	$course = learn_press_get_course( $course_id );

	return $course->get_curriculum();
}

/**
 * Verify course access
 *
 * @param int $course_id
 * @param int $user_id
 *
 * @return boolean
 * @deprecated 4.1.3
 * @editor tungnx
 */
function learn_press_is_enrolled_course( $course_id = null, $user_id = null ) {
	_deprecated_function( __FUNCTION__, '4.1.3' );
	if ( $course = learn_press_get_course( $course_id ) && $user = learn_press_get_user( $user_id ) ) {
		return $user->has_enrolled_course( $course_id );
	}

	return false;
}

/**
 * Detect if a course is free or not
 *
 * @param null $course_id
 *
 * @return bool
 */
function learn_press_is_free_course( $course_id = null ) {
	if ( ! $course_id ) {
		$course_id = get_the_ID();
	}

	return learn_press_get_course( $course_id )->is_free();
}

/**
 * get current status of user's course
 *
 * @param int $user_id
 * @param int $course_id
 *
 * @return  string
 * @author  Tunn
 *
 */
function learn_press_get_user_course_status( $user_id = null, $course_id = null ) {
	$course = learn_press_get_course( $course_id );
	$user   = learn_press_get_user( $user_id );

	if ( $course && $user ) {
		return $user->get_course_status( $course_id );
	}

	return false;
}

/**
 * Wrap function can-view-item of user object.
 *
 * @param int $item_id
 * @param int $course_id
 * @param int $user_id
 *
 * @return mixed
 * @since 3.1.0
 *
 */
//function learn_press_can_view_item( $item_id, $course_id = 0, $user_id = 0 ) {
//	if ( ! $user_id ) {
//		$user_id = get_current_user_id();
//	}
//	$user = learn_press_get_user( $user_id );
//
//	return $user->can_view_item( $item_id, $course_id );
//}

/**
 * Get course setting is enroll required or public
 *
 * @param int $course_id
 *
 * @return boolean
 * @since 0.9.5
 *
 */
function learn_press_course_enroll_required( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );

	$required = ( 'yes' == get_post_meta( $course_id, '_lpr_course_enrolled_require', true ) );

	return apply_filters( 'learn_press_course_enroll_required', $required, $course_id );
}

function learn_press_get_all_courses( $args = array() ) {
	$term    = '';
	$exclude = '';
	is_array( $args ) && extract( $args );
	$args  = array(
		'post_type'      => array( 'lp_course' ),
		'post_status'    => 'publish',
		'posts_per_page' => - 1,
		's'              => $term,
		'fields'         => 'ids',
		'exclude'        => $exclude,
	);
	$args  = apply_filters( 'learn_press_get_courses_args', $args );
	$posts = get_posts( $args );

	return apply_filters( 'learn_press_get_courses', $posts, $args );
}

function learn_press_search_post_excerpt( $where = '' ) {
	global $wp_the_query, $wpdb;

	if ( empty( $wp_the_query->query_vars['s'] ) ) {
		return $where;
	}

	$where = preg_replace(
		"/post_title\s+LIKE\s*(\'\%[^\%]+\%\')/",
		"post_title LIKE $1) OR ({$wpdb->posts}.post_excerpt LIKE $1",
		$where
	);

	return $where;
}

// add_filter( 'posts_where', 'learn_press_search_post_excerpt' );

function learn_press_get_course_user( $course_id = null ) {
	if ( ! $course_id ) {
		$course_id = get_the_ID();
	}

	return learn_press_get_user( get_post_field( 'post_author', $course_id ) );
}

/**
 * Get item types support in course curriculum.
 *
 * @param bool $keys
 *
 * @return mixed|null
 */
function learn_press_course_get_support_item_types( $keys = false ) {
	$types = array();
	if ( ! empty( $GLOBALS['learn_press_course_support_item_types'] ) ) {
		$types = $GLOBALS['learn_press_course_support_item_types'];
	}

	return apply_filters( 'learn-press/course-support-items', $keys ? array_keys( $types ) : $types, $keys );
}

/**
 * Register new type of course item
 *
 * @param string $post_type - Usually is post type
 * @param string $label - Name show for user
 */
function learn_press_course_add_support_item_type( $post_type, $label = '' ) {
	if ( empty( $GLOBALS['learn_press_course_support_item_types'] ) ) {
		$GLOBALS['learn_press_course_support_item_types'] = array();
	}
	if ( func_num_args() == 1 && is_array( func_get_arg( 0 ) ) ) {
		foreach ( func_get_arg( 0 ) as $type => $label ) {
			learn_press_course_add_support_item_type( $type, $label );
		}
	} elseif ( func_num_args() == 2 ) {
		$GLOBALS['learn_press_course_support_item_types'][ func_get_arg( 0 ) ] = func_get_arg( 1 );
	}
}

/**
 * Check if course is support an item's type.
 *
 * @param string $type
 *
 * @return bool
 */
function learn_press_is_support_course_item_type( $type ) {
	$types = learn_press_course_get_support_item_types();

	if ( is_array( $type ) ) {
		$support = true;
		foreach ( $type as $t ) {
			$support = $support && learn_press_is_support_course_item_type( $t );
		}
	} else {
		$support = is_string( $type ) && $type && ! empty( $types[ $type ] );
	}

	return $support;
}

learn_press_course_add_support_item_type(
	array(
		'lp_lesson' => __( 'Lesson', 'learnpress' ),
		'lp_quiz'   => __( 'Quiz', 'learnpress' ),
	)
);

function learn_press_add_course_item_feature( $type, $feature ) {
	$features = array();
	if ( ! empty( $GLOBALS['learn_press_course_item_features'] ) ) {
		$features = $GLOBALS['learn_press_course_item_features'];
	}

	if ( empty( $features[ $type ] ) ) {
		$features[ $type ] = array();
	}

	if ( array_search( $feature, $features ) === false ) {
		$features[ $type ] = $feature;
	}

	$GLOBALS['learn_press_course_item_features'] = $features;
}


function learn_press_get_course_id() {
	$course_id = 0;
	if ( learn_press_is_course() ) {
		$course_id = get_the_ID();
	}

	return absint( $course_id );
}

/**
 * Get the permalink of a course
 *
 * @param int $course_id
 *
 * @return string
 * @since 3.0.0
 *
 */
function learn_press_get_course_permalink( $course_id = 0 ) {
	if ( $course = learn_press_get_course( $course_id ) ) {
		return $course->get_permalink();
	}

	return false;
}


/**
 * Get the permalink of a item in a course
 *
 * @param int $course_id
 * @param int $item_id
 *
 * @return string
 * @since 3.0.0
 *
 */
function learn_press_get_course_item_permalink( int $course_id = 0, int $item_id = 0 ): string {
	$course = learn_press_get_course( $course_id );
	if ( $course ) {
		return $course->get_item_link( $item_id );
	}

	return '';
}

/**
 * @deprecated 4.2.2
 */
function learn_press_get_the_course() {
	_deprecated_function( __FUNCTION__, '4.2.2', 'learn_press_get_course' );
	return learn_press_get_course();
}

function learn_press_get_user_question_answer( $args = '' ) {
	$args     = wp_parse_args(
		$args,
		array(
			'question_id' => 0,
			'history_id'  => 0,
			'quiz_id'     => 0,
			'course_id'   => 0,
			'user_id'     => get_current_user_id(),
		)
	);
	$answered = null;
	if ( $args['history_id'] ) {
		$user_meta = learn_press_get_user_item_meta( $args['history_id'], 'question_answers', true );
		if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
			$answered = $user_meta[ $args['question_id'] ];
		}
	} elseif ( $args['quiz_id'] && $args['course_id'] ) {
		$user    = learn_press_get_user( $args['user_id'] );
		$history = $user->get_quiz_results( $args['quiz_id'], $args['course_id'] );
		if ( $history ) {
			$user_meta = learn_press_get_user_item_meta( $history->history_id, 'question_answers', true );
			if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
				$answered = $user_meta[ $args['question_id'] ];
			}
		}
	}

	return $answered;
}

function need_to_updating() {
	ob_start();
	learn_press_display_message( 'This function need to updating' );

	return ob_get_clean();
}

/* filter section item single course */
function learn_press_get_course_sections() {
	return apply_filters(
		'learn_press_get_course_sections',
		array(
			'lp_lesson',
			'lp_quiz',
		)
	);
}

function lean_press_get_course_sections() {
	_deprecated_function( __FUNCTION__, '2.1', 'learn_press_get_course_sections' );

	return learn_press_get_course_sections();
}

if ( ! function_exists( 'learn_press_get_course_item_url' ) ) {
	function learn_press_get_course_item_url( $course_id = null, $item_id = null ) {
		$course = learn_press_get_course( $course_id );

		return $course ? $course->get_item_link( $item_id ) : false;
	}
}

/**
 * Add filter to WP comment form of lesson or quiz to output ID of current course.
 *
 * @param $post_id
 *
 * @since 3.0.10
 *
 */
function learn_press_comment_post_item_course( $post_id ) {
	$course = learn_press_get_course();
	if ( ! $course ) {
		return;
	}

	echo sprintf( '<input type="hidden" name="comment-post-item-course" value="%d" />', $course->get_id() );
}

add_action( 'comment_form', 'learn_press_comment_post_item_course' );

function learn_press_item_comment_link( $link, $comment, $args, $cpage ) {

	$comment_post_ID = $comment->comment_post_ID;

	/**
	 * Validate if comment post is an item of course
	 */
	if ( ! learn_press_is_support_course_item_type( learn_press_get_post_type( $comment_post_ID ) ) ) {
		return $link;
	}

	$post_id = 0;

	/**
	 * Ensure there is a course
	 */
	if ( empty( $_POST['comment-post-item-course'] ) ) {
		$course = learn_press_get_course();
		if ( $course ) {
			$post_id = $course->get_id();
		}
	} else {
		$post_id = absint( $_POST['comment-post-item-course'] );
	}

	$course = learn_press_get_course( $post_id );
	if ( $course ) {
		$link = str_replace( get_the_permalink( $comment_post_ID ), $course->get_item_link( $comment_post_ID ), $link );
	}

	return $link;
}

add_filter( 'get_comment_link', 'learn_press_item_comment_link', 100, 4 );

/**
 * Fix redirection invalid when SG Cache is installed
 *
 * @param int $comment_id
 * @param string $status
 *
 * @since 3.0.10
 * @deprecated 4.1.6.9
 *
 */
/*function learn_press_force_refresh_course( $comment_id, $status ) {

	if ( empty( $_POST['comment-post-item-course'] ) ) {
		return;
	}

	$course_id = absint( $_POST['comment-post-item-course'] );
	$course    = learn_press_get_course( $course_id );
	$curd      = new LP_Course_CURD();
	$curd->load( $course );
}

add_action( 'comment_post', 'learn_press_force_refresh_course', 1000, 2 );*/

/**
 * @deprecated 4.1.6.9
 */
/*if ( ! function_exists( 'learn_press_get_nav_course_item_url' ) ) {
	function learn_press_get_nav_course_item_url( $course_id = null, $item_id = null, $content_only = false ) {

		$course           = learn_press_get_course( $course_id );
		$curriculum_items = $course->get_items();// LP_Helper::maybe_unserialize( $course->post->curriculum_items );
		$index            = array_search( $item_id, $curriculum_items );
		$return           = array(
			'back' => '',
			'next' => '',
		);
		if ( is_array( $curriculum_items ) ) {
			if ( array_key_exists( $index - 1, $curriculum_items ) ) {
				$back_item      = get_post( $curriculum_items[ $index - 1 ] );
				$return['back'] = array(
					'id'    => $back_item->ID,
					'link'  => $course->get_item_link( $curriculum_items[ $index - 1 ] ),
					'title' => $back_item->post_title,
				);
				if ( $content_only ) {
					$return['back']['link'] .= '?content-item-only=yes';
				}
			}
			if ( array_key_exists( $index + 1, $curriculum_items ) ) {
				$next_item      = get_post( $curriculum_items[ $index + 1 ] );
				$return['next'] = array(
					'id'    => $next_item->ID,
					'link'  => $course->get_item_link( $curriculum_items[ $index + 1 ] ),
					'title' => $next_item->post_title,
				);
				if ( $content_only ) {
					$return['next']['link'] .= '?content-item-only=yes';
				}
			}
		}

		return $return;
	}
}*/

if ( ! function_exists( 'learn_press_edit_item_link' ) ) {
	/**
	 * Displaying course items navigation
	 *
	 * @param null $item_id
	 * @param null $course_id
	 * @param bool $content_only
	 */
	function learn_press_edit_item_link( $item_id = null, $course_id = null, $content_only = false ) {
		$user = learn_press_get_current_user();
		if ( $user->can_edit_item( $item_id, $course_id ) ) : ?>
			<p class="edit-course-item-link">
				<a href="<?php echo get_edit_post_link( $item_id ); ?>">
									<?php
									_e(
										'Edit this item',
										'learnpress'
									);
									?>
						</a>
			</p>
			<?php
		endif;
	}
}
/**
 * Get course id of an item by id
 *
 * @deprecated 4.1.6.9
 */

if ( ! function_exists( 'learn_press_get_item_course_id' ) ) {

	function learn_press_get_item_course_id( $post_id, $post_type ) {
		_deprecated_function( __FUNCTION__, '4.1.6.9' );
		/*global $wpdb;

		// If the post is a course
		if ( LP_COURSE_CPT == learn_press_get_post_type( $post_id ) ) {
			return false;
		}

		if ( ! $post_types = learn_press_course_get_support_item_types( true ) ) {
			return false;
		}

		if ( ! in_array( learn_press_get_post_type( $post_id ), $post_types ) ) {
			return false;
		}

		$course_id = false;

		if ( false !== ( $courses = LP_Object_Cache::get( 'item-course-ids', 'learn-press' ) ) ) {

			foreach ( $courses as $course_id => $items ) {
				if ( in_array( $post_id, $items ) ) {
					break;
				}
				$course_id = false;
			}
		} else {
			$courses = array();
		}

		if ( false === $course_id ) {
			$query = $wpdb->prepare(
				"
				SELECT section.section_course_id
				FROM {$wpdb->learnpress_sections} AS section
				INNER JOIN {$wpdb->learnpress_section_items} AS item
				ON item.section_id = section.section_id
				WHERE item.item_id = %d
				LIMIT 1
				",
				$post_id
			);

			$course_id = apply_filters( 'learn-press/item-course-id', absint( $wpdb->get_var( $query ) ), $post_id );

			if ( $course = learn_press_get_course( $course_id ) ) {
				$courses[ $course_id ] = $course->get_items();
			}

			if ( empty( $courses[ $course_id ] ) ) {
				$courses[ $course_id ] = array();
			}

			if ( ! in_array( $post_id, $courses[ $course_id ] ) ) {
				$courses[ $course_id ][] = $post_id;
			}
			LP_Object_Cache::set( 'item-course-ids', $courses, 'learn-press' );

		}

		return $course_id;*/
	}
}

/**
 * @editor     tungnx | comment code
 * @deprecated 3.2.7.5
 */
/*function learn_press_item_sample_permalink_html( $return, $post_id, $new_title, $new_slug, $post ) {
	remove_filter( 'get_sample_permalink_html', 'learn_press_item_sample_permalink_html', 10 );

	$return = sprintf(
		'<a class="button" href="%s" target="_blank">%s</a>',
		learn_press_get_preview_url( $post_id ),
		__( 'Preview', 'learnpress' )
	);

	$return .= '<span>' . __( 'Permalink only available if the item is already assigned to a course.', 'learnpress' ) . '</span>';

	return sprintf( '<div id="learn-press-box-edit-slug">%s</div>', $return );
}*/

/**
 * @editor     tungnx | comment code
 * @deprecated 3.2.7.5
 */
/*if ( ! function_exists( 'learn_press_item_sample_permalink' ) ) {

	function learn_press_item_sample_permalink( $permalink, $post_id, $title, $name, $post ) {
		if ( ! in_array( $post->post_type, learn_press_course_get_support_item_types( true ) ) ) {
			return $permalink;
		}

		$permalink[0] = str_replace( $post->post_name, '%pagename%', $permalink[0] );

		if ( ! preg_match( '~^https?://~', $permalink[0] ) ) {
			add_filter( 'get_sample_permalink_html', 'learn_press_item_sample_permalink_html', 10, 5 );
		}

		return $permalink;
	}

}*/
//add_filter( 'get_sample_permalink', 'learn_press_item_sample_permalink', 10, 5 );

/**
 * Get preview url for LP post type.
 *
 * @param int $post_id
 *
 * @return string
 * @since 3.0.0
 *
 */
function learn_press_get_preview_url( $post_id ) {
	return
		esc_url_raw(
			add_query_arg(
				array(
					'lp-preview' => $post_id,
					'_wpnonce'   => wp_create_nonce( 'lp-preview' ),
				),
				trailingslashit( get_home_url() )
			)
		);
}

if ( ! function_exists( 'learn_press_course_item_type_link' ) ) {
	/**
	 * Add filter to WP custom post-type-link to edit the link of item
	 * with the link of it's course.
	 *
	 * @updated    12 Nov 2018
	 *
	 * @param string $post_link
	 * @param WP_Post $post
	 * @param bool $leavename
	 * @param bool $sample
	 *
	 * @editor     tungnx | comment code
	 * @return string
	 * @deprecated 3.2.7.4
	 */
	/*function learn_press_course_item_type_link( $post_link, $post, $leavename, $sample ) {

		remove_filter( 'post_type_link', 'learn_press_course_item_type_link', 10 );

		$course = learn_press_get_course();

		if ( ! $course && ( $course_id = learn_press_get_item_course( $post->ID ) ) ) {
			$course = learn_press_get_course( $course_id );
		}

		if ( learn_press_is_support_course_item_type( $post->post_type ) ) {
			// Check elementor installed and activated
			if ( did_action( 'elementor/loaded' ) ) {
				// do stuff for edit mode
				if ( ! Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					if ( $course ) {
						$post_link = $course->get_item_link( $post->ID );
					} else {
						$post_link = learn_press_get_sample_link_course_item_url( $post->ID );
					}
				}
			} else {
				if ( $course ) {
					$post_link = $course->get_item_link( $post->ID );
				} else {
					$post_link = learn_press_get_sample_link_course_item_url( $post->ID );
				}
			}
		}

		add_filter( 'post_type_link', 'learn_press_course_item_type_link', 10, 4 );

		return $post_link;
	}*/
}
//add_filter( 'post_type_link', 'learn_press_course_item_type_link', 10, 4 );

/**
 * Get grade course type can translate
 *
 * @param string $grade
 * @param bool $echo
 *
 * @return mixed|void
 */
function learn_press_course_grade_html( string $grade = '', bool $echo = true ) {
	switch ( $grade ) {
		case 'passed':
			$html = __( 'Passed', 'learnpress' );
			break;
		case 'failed':
			$html = __( 'Failed', 'learnpress' );
			break;
		case 'in-progress':
			$html = __( 'In Progress', 'learnpress' );
			break;
		default:
			$html = $grade;
			break;
	}

	// @since 3.0.0
	$html = apply_filters( 'learn-press/course/grade-html', $html, $grade );

	if ( $echo ) {
		echo wp_kses_post( $html );
	}

	return $html;
}

function learn_press_get_course_results_tooltip( $course_id ) {
	$metabox = LP_Course_Post_Type::assessment_meta_box();
	$options = $metabox['fields'][0]['options'];
	$cr      = get_post_meta( $course_id, '_lp_course_result', true );
	$tooltip = ! empty( $options[ $cr ] ) ? $options[ $cr ] : false;
	if ( $tooltip ) {
		if ( preg_match_all( '~<p.*>(.*)<\/p>~im', $tooltip, $matches ) ) {
			$tooltip = $matches[1][0];
		}
	}

	return $tooltip;
}

function learn_press_course_passing_condition( $value, $format, $course_id ) {
	$course    = learn_press_get_course( $course_id );
	$quiz_id   = $course->get_final_quiz();
	$evalution = get_post_meta( $course_id, '_lp_course_result', true );

	if ( $quiz_id && $evalution === 'evaluate_final_quiz' ) {
		$quiz  = learn_press_get_quiz( $quiz_id );
		$value = absint( $quiz->get_passing_grade() );

		if ( $format ) {
			$value = "{$value}%";
		}
	}

	return $value;
}

add_filter( 'learn-press/course-passing-condition', 'learn_press_course_passing_condition', 10, 3 );

function learn_press_remove_query_var_enrolled_course( $redirect ) {
	return esc_url_raw( remove_query_arg( 'enroll-course', $redirect ) );
}

add_filter( 'learn-press/enroll-course-redirect', 'learn_press_remove_query_var_enrolled_course' );

/**
 * Mark the user to know if they have just logged in
 * for some purpose.
 *
 * @since 3.0.0
 */
/*function learn_press_mark_user_just_logged_in() {
	LearnPress::instance()->session->set( 'user_just_logged_in', 'yes' );
}
add_action( 'wp_login', 'learn_press_mark_user_just_logged_in' );*/

function learn_press_translate_course_result_required( $course ) {
	if ( ! $course ) {
		return '';
	}

	$passing_condition = $course->get_passing_condition();

	$evaluate_type = $course->get_data( 'course_result', 'evaluate_lesson' );
	switch ( $evaluate_type ) {
		case 'evaluate_lesson':
			$label = esc_html__( 'completed lessons per the total number of lessons.', 'learnpress' );
			break;
		case 'evaluate_quiz':
			$label = esc_html__( 'passed quizzes per the total number of quizzes.', 'learnpress' );
			break;
		case 'evaluate_final_quiz':
			$label = esc_html__( 'Final Quiz', 'learnpress' );
			break;
		case 'evaluate_questions':
			$label = esc_html__( 'correct answers per the total number of questions.', 'learnpress' );
			break;
		case 'evaluate_mark':
			$label = esc_html__( 'score achieved per the total score of the questions.', 'learnpress' );
			break;
		default:
			$label = apply_filters( 'learnpress/message/evaluate/' . $evaluate_type, $evaluate_type );
			break;
	}

	return apply_filters(
		'learnpress/message/evaluate',
		wp_sprintf( '%1$s %2$s %3$s', __( 'Require', 'learnpress' ), $passing_condition . '%', $label )
	);
}
