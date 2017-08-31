<?php
/**
 * Functions that are used to init a course to reduce SQL queries
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action( 'init', '_learn_press_upgrade_table' );
/**
 * Add column parent_id into user_tables if it does not exists
 *
 * TODO: remove in next version
 */
function _learn_press_upgrade_table() {
	if ( version_compare( LEARNPRESS_VERSION, '2.1.0', '<' ) ) {
		global $wpdb;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}learnpress_user_items'" ) != $wpdb->prefix . "learnpress_user_items" ) {
			return;
		}

		$query = "SHOW COLUMNS FROM {$wpdb->prefix}learnpress_user_items LIKE 'parent_id'";
		if ( $row = $wpdb->get_var( $query ) ) {
			update_option( 'learn_press_upgrade_table_20', 'yes' );

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
	static $pages = false;
	if ( $pages == false ) {
		$pages    = array( 'courses', 'profile', 'become_a_teacher', 'checkout' );
		$page_ids = array();
		foreach ( $pages as $page ) {
			$id = get_option( 'learn_press_' . $page . '_page_id' );
			if ( $id ) {
				$page_ids[] = $id;
			}
		}
		if ( ! $page_ids ) {
			return;
		}
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE %d AND ID IN(" . join( ',', $page_ids ) . ")
			AND post_status <> %s
		", 1, 'trash' );
		if ( ! $pages = $wpdb->get_results( $query ) ) {
			return;
		}
		foreach ( $pages as $page ) {
			wp_cache_add( $page->ID, $page, 'posts' );
		}
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
		if ( ! empty( $wp_query->queried_object ) ) {
			if ( $wp_query->queried_object->post_name == $the_course ) {
				$post = $wp_query->queried_object;
			}
		}
		if ( ! $post ) {
			$post = learn_press_get_post_by_name( $the_course, 'lp_course' );
		}
	}

	if ( ! $post || $post->post_type != LP_COURSE_CPT ) {
		return $course;
	}
	_learn_press_get_course_curriculum( $post->ID );
	do_action( 'learn_press_setup_course_data_' . $post->ID );

	return $post->ID;
}

function _learn_press_count_users_enrolled_courses( $course_ids ) {
	global $wpdb;
	$counts = LP_Cache::get_enrolled_courses( false, array() );
	if ( $counts ) {
		$remove_ids = array();
		foreach ( $counts as $id => $data ) {
			$remove_ids[] = $id;
		}
		$course_ids = array_diff( $course_ids, $remove_ids );
	}
	if ( $course_ids ) {
		$in       = array_fill( 0, sizeof( $course_ids ), '%d' );
		$format   = array( '_course_id' );
		$format   = array_merge( $format, $course_ids );
		$format[] = 'lp-completed';
		$query    = $wpdb->prepare( "
			SELECT oim.meta_value as course_id, count(o.ID) as count
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = o.ID
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id
			AND oim.meta_key = %s
			AND oim.meta_value IN(" . join( ',', $in ) . ")
			WHERE o.post_status = %s
			GROUP BY oim.meta_value
		", $format );
		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $c => $v ) {
				$counts[ $v->course_id ] = absint( $v->count );
			}
		}
	}
	foreach ( $course_ids as $course_id ) {
		if ( ! array_key_exists( $course_id, $counts ) ) {
			$counts[ $course_id ] = 0;
		}
	}
	LP_Cache::set_enrolled_courses( $counts );

	return $counts;
}

function _learn_press_get_courses_curriculum( $course_ids, $force = false, $parse_items = true ) {
	global $wpdb;
	$curriculum = LP_Cache::get_course_curriculum( false, array() );
	$post_names = LP_Cache::get_post_names( false, array() );

	$remove_courses = array();
	foreach ( $course_ids as $course_id ) {
		if ( array_key_exists( $course_id, $curriculum ) ) {
			$remove_courses[] = $course_id;
		} else {
			$curriculum[ $course_id ] = array();
		}
	}

	if ( $remove_courses ) {
		$course_ids = array_diff( $course_ids, $remove_courses );
	}
	if ( ! $course_ids ) {
		return;
	}
	$in    = array_fill( 0, sizeof( $course_ids ), '%d' );
	$query = $wpdb->prepare( "
		SELECT s.*, si.*, p.*
		FROM {$wpdb->prefix}posts p
		INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = p.ID
		INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
		WHERE s.section_id IN(
			SELECT cc.section_id
				FROM {$wpdb->prefix}posts p
				INNER JOIN {$wpdb->prefix}learnpress_sections cc ON p.ID = cc.section_course_id
				WHERE p.ID IN(" . join( ',', $in ) . ")
				ORDER BY `section_order` ASC
		 )
		ORDER BY s.section_course_id, s.section_order, si.item_order ASC
	", $course_ids );
	asort( $course_ids );

	if ( $posts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID IN (" . join( ',', $in ) . ")", $course_ids ) ) ) {
		foreach ( $posts as $_post ) {
			$_post = sanitize_post( $_post, 'raw' );
			wp_cache_add( $_post->ID, $_post, 'posts' );
		}
	}
	$rows           = $wpdb->get_results( $query );
	$meta_cache_ids = $course_ids;

	if ( $rows ) {
		if ( ! function_exists( 'get_default_post_to_edit' ) ) {
			include_once ABSPATH . '/wp-admin/includes/post.php';
		}

		foreach ( $course_ids as $course_id ) {
			$_curriculum = array();

			$empty_post = (array) get_default_post_to_edit();
			$section_id = 0;
			$item_ids   = array();
			$quiz_ids   = array();
			$lesson_ids = array();

			foreach ( $rows as $row ) {
				if ( $course_id != $row->section_course_id || empty( $row->section_id ) ) {
					continue;
				}
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
					$section->items             = array();
					$_curriculum[ $section_id ] = $section;
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
				$item_ids[] = $item->ID;
				if ( $item->post_type == LP_QUIZ_CPT ) {
					if ( false == wp_cache_get( $item->ID, 'posts' ) ) {
						$quiz_ids[] = $item->ID;
					}
				} elseif ( $item->post_type == LP_LESSON_CPT ) {
					$lesson_ids[] = $item->ID;
				}
				if ( empty( $post_names[ $item->post_type ] ) ) {
					$post_names[ $item->post_type ] = array();
				}
				$post_names[ $item->post_type ][ $item->post_name ] = $item->ID;
				if ( $item->post_type == LP_QUIZ_CPT ) {
					$cached_post = wp_cache_get( $item->ID, 'posts' );
					if ( $cached_post ) {
						foreach ( array( 'mark', 'questions' ) as $prop ) {
							if ( property_exists( $cached_post, $prop ) ) {
								$item->{$prop} = $cached_post->{$prop};
							}
						}
					}
				}
				$_curriculum[ $section_id ]->items[] = $item;
				wp_cache_delete( $item->ID, 'posts' );
				wp_cache_add( $item->ID, $item, 'posts' );
			}

			$meta_cache_ids           = array_merge( $meta_cache_ids, $item_ids );
			$course                   = get_post( $course_id );
			$course->curriculum_items = is_admin() ? maybe_serialize( $item_ids ) : $item_ids;
			wp_cache_replace( $course_id, $course, 'posts' );
			if ( $quiz_ids ) {
				$fetched_posts = array();
				foreach ( $quiz_ids as $quiz_id ) {
					if ( wp_cache_get( $quiz_id, 'posts' ) ) {
						$fetched_posts[] = $quiz_id;
					}
				}
				foreach ( $quiz_ids as $quiz_id ) {
					//print_r(wp_cache_get($quiz_id, 'posts'));
				}

				if ( $fetched_posts ) {
					$quiz_ids = array_diff( $quiz_ids, $fetched_posts );

					if ( $quiz_ids ) {
						$question_ids = _learn_press_get_quiz_questions( $quiz_ids );
						if ( $question_ids ) {
							$meta_cache_ids = array_merge( $meta_cache_ids, $question_ids );
						}
					}
				}
			}
			$curriculum[ $course_id ] = $_curriculum;
		}
		$meta_cache_ids = array_unique( $meta_cache_ids );
		update_meta_cache( 'post', $meta_cache_ids );
		$attachment_ids = array();
		foreach ( $meta_cache_ids as $id ) {
			if ( $postmeta = wp_cache_get( $id, 'post_meta' ) ) {
				if ( array_key_exists( '_thumbnail_id', $postmeta ) ) {
					$attachment_ids[] = $postmeta['_thumbnail_id'][0];
				}
			}
		}
		if ( $attachment_ids ) {
			update_meta_cache( 'post', $attachment_ids );
			if ( $attachments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID IN(" . join( ',', $attachment_ids ) . ") AND post_type=%s", 'attachment' ) ) ) {
				foreach ( $attachments as $attachment ) {
					wp_cache_add( $attachment->ID, $attachment, 'posts' );
				}
			}
		}
		LP_Cache::set_course_curriculum( $curriculum );
		LP_Cache::set_post_names( $post_names );
	}

	return $curriculum;
}

/**
 * @param      $course_id
 * @param bool $force
 *
 * @return array
 */
function _learn_press_get_course_curriculum( $course_id, $force = false ) {
	//return learn_press_get_course_curriculumx( $course_id, $force );
	$curriculum = LP_Cache::get_course_curriculum( $course_id );
	if ( ( $curriculum == false ) || $force ) {
		$curriculum = _learn_press_get_courses_curriculum( array( $course_id ), $force );
		if ( empty( $curriculum[ $course_id ] ) ) {
			$curriculum[ $course_id ] = array();
		}
		$curriculum = $curriculum[ $course_id ];
	}

	return $curriculum;
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
		$q = wp_cache_get( $quiz_ids[ $i ], 'posts' );
		if ( $q && property_exists( $q, 'questions' ) ) {
			unset( $quiz_ids[ $i ] );
		}
	}
	$meta_cache_ids = array();
	if ( ! $quiz_ids ) {
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
					if ( empty( $questions[ $question_id ] ) ) {
						continue;
					}
					$questions[ $question_id ]->answers = array();
					$questions[ $question_id ]->type    = $row->type;
				}
				if ( ! $answer_data = maybe_unserialize( $row->answer_data ) ) {
					continue;
				}
				$answer_data['id']                                              = $row->question_answer_id;
				$answer_data['order']                                           = $row->answer_order;
				$answer_data['type']                                            = $row->type;
				$questions[ $question_id ]->answers[ $row->question_answer_id ] = $answer_data;
			}
		}

		foreach ( $questions as $question ) {
			if ( ! isset( $marks[ $question->quiz_id ] ) ) {
				$marks[ $question->quiz_id ] = 0;
			}
			if ( empty( $quiz_questions[ $question->quiz_id ] ) ) {
				$quiz_questions[ $question->quiz_id ] = array();
			}
			$marks[ $question->quiz_id ]            += $question->mark;
			$quiz_questions[ $question->quiz_id ][] = $question->ID;

			// Issue with FIB
			if ( false !== wp_cache_get( $question->ID, 'posts' ) ) {
				wp_cache_replace( $question->ID, $question, 'posts' );
			} else {
				wp_cache_add( $question->ID, $question, 'posts' );
			}

			$post_names[ $question->post_name ] = $question->ID;
		}

		$meta_cache_ids = array_merge( $meta_cache_ids, $question_ids );
		foreach ( $marks as $id => $mark ) {
			$quiz            = get_post( $id );
			$quiz->mark      = $mark;
			$quiz->questions = is_admin() ? maybe_serialize( $quiz_questions[ $id ] ) : $quiz_questions[ $id ];
			wp_cache_delete( $id, 'posts' );
			wp_cache_add( $id, $quiz, 'posts' );
			$post_names[ $quiz->post_name ] = $id;
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
	if ( is_array( $course_id ) ) {
		_learn_press_get_courses_curriculum( $course_id );
		foreach ( $course_id as $cid ) {
			learn_press_setup_user_course_data( $user_id, $cid, $force );
		}

		return;
	}
	if ( ! did_action( 'learn_press_setup_course_data_' . $course_id ) ) {
		learn_press_setup_course_data( $course_id );
	}

	if ( ! $course_id ) {
		$course_id = get_the_ID();
	}

	/**
	 * Get user orders
	 */
	_learn_press_get_user_course_orders();
	_learn_press_parse_user_item_statuses( $user_id, $course_id );
	global $lp_query;
	if ( ! empty( $lp_query->query_vars['lesson'] ) && ! empty( $item_statuses[ $user_id . '-' . $course_id . '-' . $course_id ] ) && $item_statuses[ $user_id . '-' . $course_id . '-' . $course_id ] != 'finished' ) {
		$user_item_id = learn_press_get_user_item_id( $user_id, $course_id );
		$lesson       = learn_press_get_post_by_name( $lp_query->query_vars['lesson'], LP_LESSON_CPT );
		if ( empty( $item_statuses[ $user_id . '-' . $course_id . '-' . $lesson->ID ] ) ) {
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
	if ( did_action( "learn_press_parse_user_item_statuses_{$user_id}_{$course_id}" ) && ! $force ) {
		return;
	}
	global $wpdb;
	if ( ! $course_id ) {
		$course_id = get_the_ID();
	}
	$course   = get_post( $course_id );
	$item_ids = ! empty( $course->curriculum_items ) ? $course->curriculum_items : array();
	$item_ids = maybe_unserialize( $item_ids );
	if ( $item_ids ) {
		$in    = implode( ', ', $item_ids );
		$query = $wpdb->prepare( "
			SELECT X.*, ui.meta_value AS grade
			FROM (
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
			) AS X
			LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta ui ON ui.learnpress_user_item_id = X.user_item_id AND ui.meta_key = %s
			ORDER BY user_item_id ASC
		", $user_id, $course_id, $user_id, $course_id, $user_id, $course_id, '_quiz_grade' );
	} else {
		$query = $wpdb->prepare( "
			SELECT ui.*, uim.meta_value as grade FROM {$wpdb->prefix}learnpress_user_items ui
			LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta uim ON uim.learnpress_user_item_id = ui.user_item_id AND uim.meta_key = %s
			WHERE user_id = %d
			AND item_id = %d
		", '_quiz_grade', $user_id, $course_id );
	}
	$items = $wpdb->get_results( $query );

	$item_statuses = LP_Cache::get_item_statuses( false, array() );
	$quiz_grades   = LP_Cache::get_quiz_grade( false, array() );
	foreach ( $item_ids as $id ) {
		if ( ! array_key_exists( $id, $item_statuses ) || $force ) {
			$item_statuses[ $user_id . '-' . $course_id . '-' . $id ] = '';
		}

		if ( ! array_key_exists( $id, $item_statuses ) || $force ) {
			$quiz_grades[ $user_id . '-' . $course_id . '-' . $id ] = '';
		}
	}
	if ( $items ) {
		foreach ( $items as $item ) {
			$item_statuses[ $user_id . '-' . $course_id . '-' . $item->item_id ] = learn_press_validate_item_status( $item );
			if ( ! empty( $item->grade ) ) {
				$quiz_grades[ $user_id . '-' . $course_id . '-' . $item->item_id ] = $item->grade;
			} else {
				$quiz_grades[ $user_id . '-' . $course_id . '-' . $item->item_id ] = '';
			}
		}
	}
	LP_Cache::set_item_statuses( $item_statuses );
	LP_Cache::set_quiz_grade( $quiz_grades );

	do_action( "learn_press_parse_user_item_statuses", $user_id, $course_id );
	do_action( "learn_press_parse_user_item_statuses_{$user_id}_{$course_id}" );
}

function learn_press_validate_item_status( $item ) {
	if ( property_exists( $item, 'end_time' ) ) {

		$end_time = $item->end_time !== '0000-00-00 00:00:00';
		$status   = $end_time > 0 ? ( $item->item_type != LP_COURSE_CPT ? 'completed' : 'finished' ) : $item->status;
		if ( $item->item_type == LP_QUIZ_CPT && $item->status == 'completed' && is_null( $item->grade ) ) {
			$user  = learn_press_get_user( $item->user_id );
			$grade = $user->get_quiz_graduation( $item->item_id, $item->ref_id );
			LP_Cache::set_quiz_grade( sprintf( '%d-%d-%d', $item->user_id, $item->ref_id, $item->item_id ), $grade );
			learn_press_update_user_item_meta( $item->user_item_id, '_quiz_grade', $grade );
		}
		if ( $end_time && ! in_array( $item->status, array( 'completed', 'finished' ) ) ) {
			global $wpdb;
			$data           = (array) $item;
			$data['status'] = $item->item_type != LP_COURSE_CPT ? 'completed' : 'finished';
			learn_press_update_user_item_field(
				$data,
				array(
					'user_item_id' => $item->user_item_id
				)
			);
		}
	} else {
		$status = $item->status;
	}

	return $status;
}

/**
 * @param int  $user_id
 * @param bool $force
 *
 * @return array
 */
function _learn_press_get_user_course_orders( $user_id = 0, $force = false ) {
	global $wpdb;
	if ( ! $user_id ) {
		$user_id = learn_press_get_current_user_id();
	}
	$data = LP_Cache::get_user_course_order( false, array() );
	if ( ! array_key_exists( $user_id, $data ) || $force ) {
		$results = array();
		$query   = $wpdb->prepare( "
			SELECT o.*, oim.meta_value as course_id
			FROM {$wpdb->prefix}learnpress_order_items oi
			INNER JOIN {$wpdb->prefix}learnpress_order_itemmeta oim ON oim.learnpress_order_item_id = oi.order_item_id AND meta_key = %s
			INNER JOIN {$wpdb->prefix}postmeta om ON om.post_id = oi.order_id AND om.meta_key = %s AND om.meta_value = %d
			INNER JOIN {$wpdb->posts} o ON o.ID = om.post_id AND o.post_status <> %s
			WHERE o.post_type = %s ORDER BY ID ASC
		", '_course_id', '_user_id', $user_id, 'trash', LP_ORDER_CPT );
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				if ( empty( $results[ $row->course_id ] ) ) {
					$results[ $row->course_id ] = array(
						$row->ID => $row
					);
				} else {
					$results[ $row->course_id ]             = array_reverse( $results[ $row->course_id ], true );
					$results[ $row->course_id ][ $row->ID ] = $row;
					$results[ $row->course_id ]             = array_reverse( $results[ $row->course_id ], true );
				}
			}
		}
		$data[ $user_id ] = $results;
		LP_Cache::set_user_course_order( $data );
	} else {
		$results = $data[ $user_id ];
	}

	return $results;
}

function _learn_press_get_user_profile_orders( $user_id = 0, $paged = 1, $limit = 10 ) {
	global $wpdb;
	if ( ! $user_id ) {
		$user_id = learn_press_get_current_user_id();
	}
	if ( empty( $paged ) ) {
		$paged = 1;
	}
	if ( empty( $limit ) ) {
		$limit = 10;
	}
	$data = LP_Cache::get_user_profile_orders( false, array() );

	if ( ! array_key_exists( $user_id, $data ) ) {
		$limit         = absint( $limit );
		$offset        = absint( $paged - 1 ) * $limit;
		$results       = array();
		$statuses      = learn_press_get_order_statuses( true, true );
		$status_format = array_fill( 0, sizeof( $statuses ), '%s' );
		$args          = array( '_user_id', $user_id, LP_ORDER_CPT, $user_id, $offset, $limit );
		array_splice( $args, 3, 0, $statuses );
		$query = $wpdb->prepare( "
			SELECT DISTINCT po.*, oi.order_id, pm.meta_value as user_id
			FROM {$wpdb->prefix}learnpress_order_items oi
			INNER JOIN {$wpdb->prefix}postmeta pm ON  pm.post_id = oi.order_id AND pm.meta_key = %s AND pm.meta_value = %d
			RIGHT JOIN {$wpdb->prefix}posts po ON po.ID = oi.order_id
			WHERE po.post_type = %s AND po.post_status IN(" . join( ',', $status_format ) . ")
			HAVING user_id = %d
			ORDER BY ID DESC
			LIMIT %d, %d
		", $args );
		if ( $rows = $wpdb->get_results( $query ) ) {
			$results['total']     = count( $rows );
			$results['paged']     = $paged;
			$results['limit']     = $limit;
			$results['offset']    = $offset;
			$results['num_pages'] = ceil( $results['total'] / $limit );
			$rows                 = array_slice( $rows, $offset, $limit );
			$order_ids            = array();
			foreach ( $rows as $row ) {
				$results['rows'][ $row->ID ] = $row;
				wp_cache_add( $row->ID, $row, 'posts' );
				$order_ids[] = $row->ID;
			}
			update_meta_cache( 'post', $order_ids );
		}

		$data[ $user_id ] = $results;
		LP_Cache::set_user_profile_orders( $data );
	} else {
		$results = $data[ $user_id ];
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
				$question_id                        = $row->ID;
				$questions[ $question_id ]          = $row;
				$questions[ $question_id ]->answers = array();
				$questions[ $question_id ]->type    = $row->type;
			}
			if ( ! $answer_data = maybe_unserialize( $row->answer_data ) ) {
				continue;
			}
			$answer_data['id']                                              = $row->question_answer_id;
			$answer_data['order']                                           = $row->answer_order;
			$answer_data['type']                                            = $row->type;
			$questions[ $question_id ]->answers[ $row->question_answer_id ] = $answer_data;
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