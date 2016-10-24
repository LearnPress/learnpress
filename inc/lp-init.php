<?php
/**
 * Functions that are used to init a course to reduce SQL queries
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action( 'init', '_learn_press_upgrade_table' );
/**
 * Add column parent_id into user_tables if it does not exists
 *
 * TODO: remove in next version
 */
function _learn_press_upgrade_table() {
	if ( get_option( 'learn_press_upgrade_table_20' ) != 'yes' ) {
		global $wpdb;
		$query = "SHOW COLUMNS FROM {$wpdb->prefix}learnpress_user_items LIKE 'parent_id'";
		if ( $row = $wpdb->get_var( $query ) ) {
			return;
		}
		$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_items ADD COLUMN `parent_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER `ref_type`;";
		if ( $wpdb->query( $query ) ) {
			update_option( 'learn_press_upgrade_table_20', 'yes' );
		}
	}
}

add_action( 'learn_press_parse_query', '_learn_press_setup_user_course_data' );
function _learn_press_setup_user_course_data( $query ) {
	learn_press_setup_user_course_data( get_current_user_id(), $query->query_vars['course_id'] );
}

/**
 * Cache static pages
 */
function learn_press_setup_pages() {
	global $wpdb;
	$pages    = array( 'courses', 'profile', 'become_a_teacher', 'checkout' );
	$page_ids = array();
	foreach ( $pages as $page ) {
		$id = get_option( 'learn_press_' . $page . '_page_id' );
		if ( $id ) {
			$page_ids[] = $id;
		}
	}
	if ( !$page_ids ) {
		return;
	}
	$query = $wpdb->prepare( "
		SELECT *
		FROM {$wpdb->posts}
		WHERE %d AND ID IN(" . join( ',', $page_ids ) . ")
		AND post_status <> %s
	", 1, 'trash' );
	if ( !$pages = $wpdb->get_results( $query ) ) {
		return;
	}
	foreach ( $pages as $page ) {
		wp_cache_add( $page->ID, $page, 'posts' );
	}
}

/**
 * Setup course
 *
 * @param $the_course
 *
 * @return bool|int
 */
function learn_press_setup_course_data( $the_course ) {
	global $wp_query;
	$course = false;
	$post   = false;
	if ( is_numeric( $the_course ) ) {
		$post = get_post( $the_course );
	} elseif ( $the_course instanceof LP_Abstract_Course ) {
		$post = $the_course->post;
	} elseif ( isset( $the_course->ID ) ) {
		$post = $the_course;
	} elseif ( is_string( $the_course ) ) {
		if ( !empty( $wp_query->queried_object ) ) {
			if ( $wp_query->queried_object->post_name == $the_course ) {
				$post = $wp_query->queried_object;
			}
		}
		if ( !$post ) {
			$post = learn_press_get_post_by_name( $the_course, 'lp_course' );
		}
	}

	if ( !$post || $post->post_type != LP_COURSE_CPT ) {
		return $course;
	}
	_learn_press_get_course_curriculum( $post->ID );
	do_action( 'learn_press_setup_course_data_' . $post->ID );

	return $post->ID;
}

/**
 * @param      $course_id
 * @param bool $force
 *
 * @return array
 */
function _learn_press_get_course_curriculum( $course_id, $force = false ) {
	$curriculum     = LP_Cache::get_course_curriculum( false, array() );
	$post_names     = LP_Cache::get_post_names( false, array() );
	$meta_cache_ids = array();
	if ( !array_key_exists( $course_id, $curriculum ) || $force ) {
		global $wpdb;
		$query       = $wpdb->prepare( "
			SELECT s.*, si.*, p.*
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = p.ID
			INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
			WHERE s.section_id IN(
				SELECT cc.section_id
					FROM {$wpdb->prefix}posts p
					INNER JOIN {$wpdb->prefix}learnpress_sections cc ON p.ID = cc.section_course_id
					WHERE p.ID = %d
					ORDER BY `section_order` ASC
			 )
			ORDER BY s.section_order, si.item_order ASC
		", $course_id );
		$_curriculum = array();
		$rows        = $wpdb->get_results( $query );
		if ( !$rows ) {
			return false;
		}
		if ( !function_exists( 'get_default_post_to_edit' ) ) {
			include_once ABSPATH . '/wp-admin/includes/post.php';
		}
		$empty_post = (array) get_default_post_to_edit();
		$section_id = 0;
		$item_ids   = array();
		$quiz_ids   = array();
		$lesson_ids = array();
		foreach ( $rows as $row ) {
			if ( $row->section_id != $section_id ) {
				$section_id = $row->section_id;
				$section    = new stdClass();
				foreach (
					array(
						'section_id',
						'section_name',
						'section_course_id',
						'section_order',
						'section_description'
					) as $prop
				) {
					$section->{$prop} = $row->{$prop};
				}
				$section->items           = array();
				$_curriculum[$section_id] = $section;
			}
			$item = new stdClass();
			foreach ( array( 'section_item_id', 'section_id', 'item_id', 'item_order', 'item_type' ) as $prop ) {
				$item->{$prop} = $row->{$prop};
			}

			foreach ( $empty_post as $prop => $value ) {
				if ( property_exists( $row, $prop ) ) {
					$item->{$prop} = $row->{$prop};
				}
			}
			$_curriculum[$section_id]->items[] = $item;
			$item_ids[]                        = $item->ID;
			if ( $item->post_type == LP_QUIZ_CPT ) {
				$quiz_ids[] = $item->ID;
			} elseif ( $item->post_type == LP_LESSON_CPT ) {
				$lesson_ids[] = $item->ID;
			}
			if ( empty( $post_names[$item->post_type] ) ) {
				$post_names[$item->post_type] = array();
			}
			$post_names[$item->post_type][$item->post_name] = $item->ID;
			wp_cache_delete( $item->ID, 'posts' );
			wp_cache_add( $item->ID, $item, 'posts' );
		}
		$meta_cache_ids           = array_merge( $meta_cache_ids, $item_ids );
		$course                   = get_post( $course_id );
		$course->curriculum_items = is_admin() ? maybe_serialize( $item_ids ) : $item_ids;
		wp_cache_replace( $course_id, $course, 'posts' );
		if ( $quiz_ids ) {
			$question_ids = _learn_press_get_quiz_questions( $quiz_ids );
			if ( $question_ids ) {
				$meta_cache_ids = array_merge( $meta_cache_ids, $question_ids );
			}
		}
		update_meta_cache( 'post', $meta_cache_ids );
		$curriculum[$course_id] = $_curriculum;
		LP_Cache::set_course_curriculum( $curriculum );
	} else {
		$_curriculum = $curriculum[$course_id];
	}
	LP_Cache::set_post_names( $post_names );

	return $_curriculum;
}

/**
 * @param $quiz_ids
 *
 * @return array
 */
function _learn_press_get_quiz_questions( $quiz_ids ) {
	global $wpdb;
	settype( $quiz_ids, 'array' );
	for ( $n = sizeof( $quiz_ids ), $i = $n - 1; $i >= 0; $i -- ) {
		$q = wp_cache_get( $quiz_ids[$i], 'posts' );
		if ( $q && property_exists( $q, 'questions' ) ) {
			unset( $quiz_ids[$i] );
		}
	}
	$meta_cache_ids = array();
	if ( !$quiz_ids ) {
		return $meta_cache_ids;
	}
	$marks          = array();
	$quiz_questions = array();
	$query          = $wpdb->prepare( "
		SELECT  p.*, qq.quiz_id,IF(pm.meta_value, pm.meta_value, 1) as mark
		FROM {$wpdb->prefix}learnpress_quiz_questions qq
		INNER JOIN {$wpdb->prefix}posts p ON p.ID = qq.question_id AND p.post_type = %s
		INNER JOIN {$wpdb->prefix}posts q ON q.ID = qq.quiz_id AND q.post_type = %s
		LEFT JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = qq.question_id AND pm.meta_key = %s
		WHERE %d AND qq.quiz_id IN (" . join( ',', $quiz_ids ) . ")
		ORDER BY quiz_id, qq.question_order ASC
	", 'lp_question', 'lp_quiz', '_lp_mark', 1 );
	if ( $questions = $wpdb->get_results( $query, OBJECT_K ) ) {
		$question_ids = array_keys( $questions );
		$format_ids   = array_fill( 0, sizeof( $question_ids ), '%d' );
		$prepare_args = array_merge( array( '_lp_type', 'lp_question' ), $question_ids );
		$query        = $wpdb->prepare( "
					SELECT qa.question_answer_id, ID as id, pm.meta_value as type, qa.answer_data as answer_data, answer_order
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
					INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON qq.question_id = p.ID
					RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
					WHERE qq.quiz_id IN (" . join( ',', $quiz_ids ) . ")
					ORDER BY id, qq.question_order, answer_order ASC
				", $prepare_args );
		if ( $answers = $wpdb->get_results( $query ) ) {
			$question_id = 0;
			foreach ( $answers as $row ) {
				if ( $row->id != $question_id ) {
					$question_id = $row->id;
					if ( empty( $questions[$question_id] ) ) {
						continue;
					}
					$questions[$question_id]->answers = array();
					$questions[$question_id]->type    = $row->type;
				}
				if ( !$answer_data = maybe_unserialize( $row->answer_data ) ) {
					continue;
				}
				$answer_data['id']                                          = $row->question_answer_id;
				$answer_data['order']                                       = $row->answer_order;
				$answer_data['type']                                        = $row->type;
				$questions[$question_id]->answers[$row->question_answer_id] = $answer_data;
			}
		}

		foreach ( $questions as $question ) {
			if ( !isset( $marks[$question->quiz_id] ) ) {
				$marks[$question->quiz_id] = 0;
			}
			if ( empty( $quiz_questions[$question->quiz_id] ) ) {
				$quiz_questions[$question->quiz_id] = array();
			}
			$marks[$question->quiz_id] += $question->mark;
			$quiz_questions[$question->quiz_id][] = $question->ID;
			wp_cache_add( $question->ID, $question, 'posts' );
			$post_names[$question->post_name] = $question->ID;
		}

		$meta_cache_ids = array_merge( $meta_cache_ids, $question_ids );
		foreach ( $marks as $id => $mark ) {
			$quiz            = get_post( $id );
			$quiz->mark      = $mark;
			$quiz->questions = is_admin() ? maybe_serialize( $quiz_questions[$id] ) : $quiz_questions[$id];
			wp_cache_delete( $id, 'posts' );
			wp_cache_add( $id, $quiz, 'posts' );
			$post_names[$quiz->post_name] = $id;
		}
	}
	$fetched_ids = array_keys( $marks );
	$no_fetched  = array_diff( $quiz_ids, $fetched_ids );
	if ( $no_fetched ) {
		foreach ( $no_fetched as $id ) {
			$q            = wp_cache_get( $id, 'posts' );
			$q->mark      = 1;
			$q->questions = '';
			wp_cache_replace( $id, $q, 'posts' );
		}
	}

	return $meta_cache_ids;
}

/**
 * @param $user_id
 * @param $course_id
 */
function learn_press_setup_user_course_data( $user_id, $course_id, $force = false ) {

	if ( !did_action( 'learn_press_setup_course_data_' . $course_id ) ) {
		learn_press_setup_course_data( $course_id );
	}
	if ( !did_action( 'learn_press_parse_query' ) ) {
		_doing_it_wrong( __FUNCTION__, __( '' ), LEARNPRESS_VERSION );

		return;
	}

	if ( !$course_id ) {
		$course_id = get_the_ID();
	}

	/**
	 * Get user orders
	 */
	_learn_press_get_user_course_orders();
	_learn_press_parse_user_item_statuses( $user_id, $course_id );
	global $lp_query;
	if ( !empty( $lp_query->query_vars['lesson'] ) && !empty( $item_statuses[$user_id . '-' . $course_id . '-' . $course_id] ) && $item_statuses[$user_id . '-' . $course_id . '-' . $course_id] != 'finished' ) {
		$user_item_id = learn_press_get_user_item_id( $user_id, $course_id );
		$lesson       = learn_press_get_post_by_name( $lp_query->query_vars['lesson'], LP_LESSON_CPT );
		if ( empty( $item_statuses[$user_id . '-' . $course_id . '-' . $lesson->ID] ) ) {
			learn_press_update_user_item_field( array(
				'user_id'    => $user_id,
				'item_id'    => $lesson->ID,
				'start_time' => current_time( 'mysql' ),
				'item_type'  => get_post_type( $lesson->ID ),
				'ref_type'   => LP_COURSE_CPT,
				'status'     => get_post_type( $lesson->ID ) == LP_LESSON_CPT ? 'started' : 'viewed',
				'ref_id'     => $course_id,
				'parent_id'  => $user_item_id
			) );
		}
	}
}

function _learn_press_parse_user_item_statuses( $user_id, $course_id, $force = false ) {
	if ( did_action( "learn_press_parse_user_item_statuses_{$user_id}_{$course_id}" ) ) {
		return;
	}
	global $wpdb;
	if ( !$course_id ) {
		$course_id = get_the_ID();
	}
	$course   = get_post( $course_id );
	$item_ids = !empty( $course->curriculum_items ) ? $course->curriculum_items : array();
	$item_ids = maybe_unserialize( $item_ids );
	if ( $item_ids ) {
		$in    = implode( ', ', $item_ids );
		$query = $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}learnpress_user_items t1
			WHERE user_id = %d
			AND item_id = %d
			UNION
			(
				SELECT * FROM {$wpdb->prefix}learnpress_user_items t2
				WHERE user_id = %d AND ref_id = %d
				AND user_item_id = (
					SELECT MAX(user_item_id) FROM {$wpdb->prefix}learnpress_user_items
					WHERE user_id = %d and ref_id = %d
					AND item_id = t2.item_id
				)
				AND item_id IN({$in})
			)
			ORDER BY user_item_id ASC
		", $user_id, $course_id, $user_id, $course_id, $user_id, $course_id );
	} else {
		$query = $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}learnpress_user_items t1
			WHERE user_id = %d
			AND item_id = %d
		", $user_id, $course_id );
	}
	$items = $wpdb->get_results( $query );

	$item_statuses = LP_Cache::get_item_statuses( false, array() );
	foreach ( $item_ids as $id ) {
		if ( !array_key_exists( $id, $item_statuses ) || $force ) {
			$item_statuses[$user_id . '-' . $course_id . '-' . $id] = '';
		}
	}
	if ( $items ) {
		foreach ( $items as $item ) {
			$item_statuses[$user_id . '-' . $course_id . '-' . $item->item_id] = $item->status;
		}
	}

	LP_Cache::set_item_statuses( $item_statuses );

	do_action( "learn_press_parse_user_item_statuses", $user_id, $course_id );
	do_action( "learn_press_parse_user_item_statuses_{$user_id}_{$course_id}" );
}

/**
 * @param int  $user_id
 * @param bool $force
 *
 * @return array
 */
function _learn_press_get_user_course_orders( $user_id = 0, $force = false ) {
	global $wpdb;
	if ( !$user_id ) {
		$user_id = learn_press_get_current_user_id();
	}
	$data = LP_Cache::get_user_course_order( false, array() );
	if ( !array_key_exists( $user_id, $data ) || $force ) {
		$results = array();
		$query   = $wpdb->prepare( "
			SELECT o.*, oim.meta_value as course_id
			FROM {$wpdb->prefix}learnpress_order_items oi
			INNER JOIN {$wpdb->prefix}learnpress_order_itemmeta oim ON oim.learnpress_order_item_id = oi.order_item_id AND meta_key = %s
			INNER JOIN {$wpdb->prefix}postmeta om ON om.post_id = oi.order_id AND om.meta_key = %s AND om.meta_value = %d
			INNER JOIN {$wpdb->posts} o ON o.ID = om.post_id AND o.post_status <> %s
			WHERE o.post_type = %s
		", '_course_id', '_user_id', $user_id, 'trash', LP_ORDER_CPT );
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				$results[$row->course_id] = $row;
			}
		}
		$data[$user_id] = $results;
		LP_Cache::set_user_course_order( $data );
	} else {
		$results = $data[$user_id];
	}

	return $results;
}

/**
 * @param $id
 *
 * @return array
 */
function _learn_press_setup_question( $id ) {
	global $wpdb;
	settype( $id, 'array' );
	$query     = $wpdb->prepare( "
		SELECT p.*, qa.question_answer_id, pm.meta_value as type, qa.answer_data as answer_data, answer_order
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
		RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
		WHERE p.ID IN (" . join( ',', $id ) . ")
		ORDER BY ID, answer_order ASC
	", '_lp_type' );
	$questions = array();

	if ( $answers = $wpdb->get_results( $query ) ) {
		$question_id = 0;
		foreach ( $answers as $row ) {
			if ( $row->ID != $question_id ) {
				$question_id                      = $row->ID;
				$questions[$question_id]          = $row;
				$questions[$question_id]->answers = array();
				$questions[$question_id]->type    = $row->type;
			}
			if ( !$answer_data = maybe_unserialize( $row->answer_data ) ) {
				continue;
			}
			$answer_data['id']		= $row->question_answer_id;
			$answer_data['order']	= $row->answer_order;
			$answer_data['type']	= $row->type;
			$questions[$question_id]->answers[$row->question_answer_id] = $answer_data;
		}
		foreach ( $questions as $question ) {
			$question->answers = maybe_serialize( $question->answers );
			wp_cache_delete( $question->ID, 'posts' );
			wp_cache_add( $question->ID, $question, 'posts' );
		}
	}

	return $questions;
}

learn_press_setup_pages();

#
# stop support comment for 
#
//add_action( 'init', 'learn_press_remove_course_comment' );
//
//function learn_press_remove_course_comment() {
//	remove_post_type_support( 'lp_course', 'comments' );
//}