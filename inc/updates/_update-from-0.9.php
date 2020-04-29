<?php
/**
 * Helper tool to uprgade database from 0.9.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Upgrade_From_09
 */
class LP_Upgrade_From_09 {
	/**
	 * All steps for update actions
	 *
	 * @var array
	 */
	protected $_steps = array(
		'welcome',
		'upgraded'
	);

	/**
	 * Current step
	 *
	 * @var string
	 */
	protected $_current_step = '';

	/**
	 * @var array
	 */
	public static $courses_map = array();

	/**
	 * @var array
	 */
	public static $orders_map = array();

	/**
	 * @var array
	 */
	public static $course_order_map = array();

	/**
	 * @var array
	 */
	public static $quizzes_map = array();

	/**
	 * @var array
	 */
	public static $lessons_map = array();

	/**
	 * @var array
	 */
	public static $questions_map = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_prevent_access_admin();
		$this->learn_press_upgrade_10_page();
	}

	/**
	 * Redirect user to Dashboard if they are trying to access the menus in old version
	 */
	private function _prevent_access_admin() {
		if ( ( $this->check_post_types() ) ) {
			wp_redirect( admin_url( 'index.php' ) );
			exit;
		}
	}

	/**
	 * Check if user trying to access the old custom post type
	 *
	 * @return bool
	 */
	private function check_post_types() {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : '';
		if ( ! $post_type ) {
			$post_id = ! empty( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;
			if ( $post_id ) {
				$post_type = get_post_field( $post_id, 'post_type' );
			}
		}
		$old_post_types = array( 'lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order' );

		return in_array( $post_type, $old_post_types );
	}

	/**
	 * Check if user trying to access LearnPress admin menu
	 *
	 * @return bool
	 */
	private function check_admin_menu() {
		$admin_page = ! empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';

		return preg_match( '!^learn_press_!', $admin_page );
	}

	/**
	 * Any value is null, empty, false, 'no', 'off' consider is false
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	private function _is_false_value( $value ) {
		if ( is_numeric( $value ) ) {
			return $value == 0;
		} elseif ( is_string( $value ) ) {
			return ( empty( $value ) || is_null( $value ) || in_array( $value, array( 'no', 'off', 'false' ) ) );
		}

		return ! ! $value;
	}

	/**
	 * Converts old curriculum to new
	 *
	 * @param $old_id
	 * @param $new_id
	 *
	 * @return array
	 */
	private function _create_curriculum( $old_id, $new_id ) {
		global $wpdb;
		$curriculum    = get_post_meta( $old_id, '_lpr_course_lesson_quiz', true );
		$section_items = array();
		$post_ids      = array();
		if ( $curriculum ) {
			foreach ( $curriculum as $order => $section ) {
				$result = $wpdb->insert(
					$wpdb->prefix . 'learnpress_sections',
					array(
						'section_name'      => $section['name'],
						'section_course_id' => $new_id,
						'section_order'     => $order + 1
					),
					array( '%s', '%d', '%d' )
				);
				if ( $result ) {
					$section_id  = $wpdb->insert_id;
					$lesson_quiz = ! empty( $section['lesson_quiz'] ) ? $section['lesson_quiz'] : array();
					$post_ids    = array_merge( $post_ids, $lesson_quiz );
					$lesson_quiz = self::get_posts_by_ids( $lesson_quiz, array( 'lpr_lesson', 'lpr_quiz' ) );
					if ( ! $lesson_quiz ) {
						continue;
					}
					$order = 1;
					foreach ( $lesson_quiz as $obj ) {
						if ( $obj['post_type'] == 'lpr_quiz' ) {
							$obj['post_type'] = LP_QUIZ_CPT;
						} elseif ( $obj['post_type'] == 'lpr_lesson' ) {
							$obj['post_type'] = LP_LESSON_CPT;
						}
						$obj_id = $obj['ID'];
						unset( $obj['ID'] );
						$return = array();
						if ( $new_obj_id = wp_insert_post( $obj ) ) {
							$wpdb->insert(
								$wpdb->prefix . 'learnpress_section_items',
								array(
									'section_id' => $section_id,
									'item_id'    => $new_obj_id,
									'item_order' => $order ++,
									'item_type'  => $obj['post_type']
								)
							);
							$return['id'] = $new_obj_id;
							if ( $obj['post_type'] == LP_QUIZ_CPT ) {
								$this->_create_quiz_meta( $obj_id, $new_obj_id );
								$new_questions                = $this->_create_quiz_questions( $obj_id, $new_obj_id );
								$return['questions']          = $new_questions;
								self::$quizzes_map[ $obj_id ] = $new_obj_id;
							} elseif ( $obj['post_type'] == LP_LESSON_CPT ) {
								$this->_create_lesson_meta( $obj_id, $new_obj_id );
								$this->_update_lesson_tag( $obj_id, $new_obj_id );
								$this->_update_lesson_format( $obj_id, $new_obj_id );
								self::$lessons_map[ $obj_id ] = $new_obj_id;
							}
						}
						$section_items[ $obj_id ] = $return;
					}
				}
			}
		}

		return $section_items;
	}

	/**
	 * Upgrade items is not assigned to any courses
	 */
	private function _upgrade_unassigned_items( $force = false ) {
		global $wpdb;
		$exclude = array();
		if ( self::$quizzes_map ) {
			$exclude = array_keys( self::$quizzes_map );
		}
		if ( self::$lessons_map ) {
			$exclude = array_merge( $exclude, array_keys( self::$lessons_map ) );
		}
		$exclude = array_filter( $exclude );
		$query   = $wpdb->prepare( "
			SELECT DISTINCT p.*, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type IN (%s, %s)
			" . ( $exclude ? "AND ID NOT IN(" . join( ',', $exclude ) . ")" : "" ) . "
			" . ( ! $force ? "HAVING upgraded IS NULL" : "" ) . "
		", '_learn_press_upgraded', 'lpr_quiz', 'lpr_lesson' );

		if ( ! $items = $wpdb->get_results( $query, OBJECT_K ) ) {
			return;
		}
		foreach ( $items as $obj ) {
			$obj = (array) $obj;
			if ( $obj['post_type'] == 'lpr_quiz' ) {
				$obj['post_type'] = LP_QUIZ_CPT;
			} elseif ( $obj['post_type'] == 'lpr_lesson' ) {
				$obj['post_type'] = LP_LESSON_CPT;
			}
			$obj_id = $obj['ID'];
			unset( $obj['ID'] );
			$return = array();
			if ( $new_obj_id = wp_insert_post( $obj ) ) {
				if ( $obj['post_type'] == LP_QUIZ_CPT ) {
					$this->_create_quiz_meta( $obj_id, $new_obj_id );
					$new_questions                = $this->_create_quiz_questions( $obj_id, $new_obj_id );
					$return['questions']          = $new_questions;
					self::$quizzes_map[ $obj_id ] = $new_obj_id;
				} elseif ( $obj['post_type'] == LP_LESSON_CPT ) {
					$this->_create_lesson_meta( $obj_id, $new_obj_id );
					$this->_update_lesson_tag( $obj_id, $new_obj_id );
					$this->_update_lesson_format( $obj_id, $new_obj_id );
					self::$lessons_map[ $obj_id ] = $new_obj_id;
				}
			}
		}
	}

	/**
	 * Upgrade items is not assigned to any questions
	 */
	private function _upgrade_unassigned_questions( $force = false ) {
		global $wpdb;
		$exclude = array();
		if ( self::$questions_map ) {
			$exclude = array_keys( self::$questions_map );
		}
		$exclude = array_filter( $exclude );
		$query   = $wpdb->prepare( "
			SELECT DISTINCT p.*, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type IN (%s)
			" . ( $exclude ? "AND ID NOT IN(" . join( ',', $exclude ) . ")" : "" ) . "
			" . ( ! $force ? "HAVING upgraded IS NULL" : "" ) . "
		", '_learn_press_upgraded', 'lpr_question' );

		if ( ! $items = $wpdb->get_results( $query, OBJECT_K ) ) {
			return;
		}
		foreach ( $items as $obj ) {
			$obj    = (array) $obj;
			$obj_id = $obj['ID'];
			unset( $obj['ID'] );
			$obj['post_type'] = LP_QUESTION_CPT;
			if ( $new_obj_id = wp_insert_post( $obj ) ) {
				$this->_create_question_meta( $obj_id, $new_obj_id );
				$this->_update_question_tag( $obj_id, $new_obj_id );
				self::$questions_map[ $obj_id ] = $new_obj_id;
			}
		}
	}

	/**
	 * @param $old_id
	 * @param $new_id
	 */
	private function _update_lesson_tag( $old_id, $new_id ) {
		$tags = array();
		if ( $terms = wp_get_object_terms( $old_id, array( 'lesson_tag', 'lesson-tag' ) ) ) {
			foreach ( $terms as $term ) {
				if ( $term->taxonomy == 'lesson-tag' ) {
					$_term = term_exists( $term->name, 'lesson_tag' );
					if ( $_term === 0 || $_term === null ) {
						$_term = wp_insert_term(
							$term->name, // the term
							'lesson_tag', // the taxonomy
							array(
								'description' => $term->description,
								'slug'        => $term->slug,
								'parent'      => $term->parent
							)
						);
					}
					if ( ! is_wp_error( $_term ) ) {
						$tags[] = absint( $_term['term_id'] );
					}
				} else {
					$tags[] = abs( $term->term_id );
				}
			}
		}
		if ( $tags ) {
			wp_set_object_terms( $new_id, $tags, 'lesson_tag' );
		}
	}

	public function _update_lesson_format( $old_id, $new_id ) {
		if ( $format = get_post_format( $old_id ) ) {
			set_post_format( $new_id, $format );
		}
	}

	/**
	 * Converts old course meta to new
	 *
	 * @param $old_id
	 * @param $new_id
	 */
	private function _create_course_meta( $old_id, $new_id ) {
		$keys        = array(
			'_lpr_course_duration'           => '_lp_duration',
			'_lpr_course_price'              => '_lp_price',
			'_lpr_course_student'            => '_lp_students',
			'_lpr_max_course_number_student' => '_lp_max_students',
			'_lpr_retake_course'             => '_lp_retake_count',
			'_lpr_course_final'              => '_lp_final_quiz',
			'_lpr_course_condition'          => '_lp_passing_condition',
			'_lpr_course_enrolled_require'   => '_lp_required_enroll',
			'_lpr_course_payment'            => '_lp_payment',
			'_lpr_course_certificate'        => '_lp_cert'
		);
		$course_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
		if ( $course_meta ) {
			foreach ( $course_meta as $meta ) {
				$new_key   = $keys[ $meta['meta_key'] ];
				$new_value = $meta['meta_value'];
				switch ( $new_key ) {
					//
					case '_lp_payment':
						if ( $new_value == 'free' ) {
							$new_value = 'no';
						} elseif ( $new_value == 'not_free' ) {
							$new_value = 'yes';
						}
						break;
					case '_lp_required_enroll':
						if ( $this->_is_false_value( $new_value ) ) {
							$new_value = 'no';
						} else {
							$new_value = 'yes';
						}
						break;
					case '_lp_cert': // update certificate
						$cert_id   = absint( $new_value );
						$cert_data = get_post_meta( $cert_id, '_lpr_cert', true );
						$cert_post = get_post( $cert_id );
						if ( $cert_post && $cert_post->post_type == 'lpr_certificate' ) {
							$cert_post = get_object_vars( $cert_post );

							unset( $cert_post['ID'] );
							$cert_post['post_type'] = 'lp_cert';
							$new_cert_id            = wp_insert_post( $cert_post );
							if ( $new_cert_id ) {

								if ( ! empty( $cert_data['id'] ) ) {
									$attachment_id = $cert_data['id'];
									$attachment    = wp_get_attachment_url( $attachment_id );
									update_post_meta( $new_cert_id, '_lp_cert_template', $attachment );

									if ( ! empty( $cert_data['layers'] ) ) {
										update_post_meta( $new_cert_id, '_lp_cert_layers', $cert_data['layers'] );
									}
								}
								update_post_meta( $new_id, '_lp_cert', $new_cert_id );
							}
						}
						continue;
				}
				add_post_meta( $new_id, $new_key, $new_value );
			}
		}

		/**
		 * bbPress addon
		 */
		if ( $forum_id = get_post_meta( $old_id, '_lpr_forum_course_id', true ) ) {
			add_post_meta( $new_id, '_lp_course_forum', $forum_id );
			add_post_meta( $new_id, '_lp_bbpress_forum_enable', 'yes' );
			add_post_meta( $new_id, '_lp_bbpress_forum_enrolled_user', 'yes' );

		}

		/**
		 * Co-Instructors addon
		 */
		if ( $co_instructors = get_post_meta( $old_id, '_lpr_co_teacher' ) ) {
			foreach ( $co_instructors as $user_id ) {
				add_post_meta( $new_id, '_lp_co_teacher', $user_id );
			}
		}

		/**
		 * Update other meta data
		 */
		$this->update_post_metas(
			$old_id,
			$new_id,
			array_merge( array_keys( $keys ), array( '_lpr_forum_course_id', '_lpr_co_teacher' ) )
		);
	}

	private function _create_quiz_meta( $old_id, $new_id ) {
		$keys      = array(
			'_lpr_quiz_questions'       => null,
			'_lpr_duration'             => '_lp_duration',
			'_lpr_retake_quiz'          => '_lp_retake_count',
			'_lpr_show_quiz_result'     => '_lp_show_result',
			'_lpr_show_question_answer' => '_lp_show_check_answer',
			'_lpr_course'               => null
		);
		$quiz_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
		if ( $quiz_meta ) {
			foreach ( $quiz_meta as $meta ) {
				if ( ! $keys[ $meta['meta_key'] ] ) {
					continue;
				}
				$new_key   = $keys[ $meta['meta_key'] ];
				$new_value = $meta['meta_value'];
				switch ( $new_key ) {
					case '_lp_show_result':
					case '_lp_show_check_answer':
						if ( $this->_is_false_value( $new_value ) ) {
							$new_value = 'no';
						} else {
							$new_value = 'yes';
						}
				}
				add_post_meta( $new_id, $new_key, $new_value );
			}
		}
		//update_post_meta( $new_id, '_lp_show_explanation', 'no' );
		update_post_meta( $new_id, '_lp_show_hint', 'yes' );
		/**
		 * Update other meta data
		 */
		$this->update_post_metas(
			$old_id,
			$new_id,
			array_keys( $keys )
		);
		$this->_mark_upgraded( $old_id, $new_id );
	}

	private function _create_lesson_meta( $old_id, $new_id ) {
		$keys      = array(
			'_lpr_lesson_duration' => '_lp_duration',
			'_lpr_lesson_preview'  => '_lp_preview',
			'_lpr_course'          => null
		);
		$quiz_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
		if ( $quiz_meta ) {
			foreach ( $quiz_meta as $meta ) {
				if ( ! $keys[ $meta['meta_key'] ] ) {
					continue;
				}
				$new_key   = $keys[ $meta['meta_key'] ];
				$new_value = $meta['meta_value'];
				switch ( $new_key ) {
					case '_lp_preview':
						if ( $this->_is_false_value( $new_value ) || $new_value == 'not_preview' ) {
							$new_value = 'no';
						} else {
							$new_value = 'yes';
						}
				}
				add_post_meta( $new_id, $new_key, $new_value );
			}
		}
		if ( $post_thumbnail_id = get_post_thumbnail_id( $old_id ) ) {
			set_post_thumbnail( $new_id, $post_thumbnail_id );
		}

		/**
		 * Update other meta data
		 */
		$this->update_post_metas(
			$old_id,
			$new_id,
			array_keys( $keys )
		);
		$this->_mark_upgraded( $old_id, $new_id );
	}

	private function _create_quiz_questions( $old_quiz_id, $new_quiz_id ) {
		$_items = get_post_meta( $old_quiz_id, '_lpr_quiz_questions', true );
		if ( ! $_items ) {
			return 0;
		}
		$_items     = array_keys( $_items );
		$_questions = $this->get_posts_by_ids( $_items );
		if ( ! $_questions ) {
			return 0;
		}
		global $wpdb;
		$new_questions = array();
		$order         = 0;
		foreach ( $_questions as $question ) {
			$post_data       = (array) $question;
			$old_question_id = $post_data['ID'];
			unset( $post_data['ID'] );
			$post_data['post_type'] = LP_QUESTION_CPT;
			$new_question_id        = wp_insert_post( $post_data );
			if ( $new_question_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'learnpress_quiz_questions',
					array(
						'quiz_id'        => $new_quiz_id,
						'question_id'    => $new_question_id,
						'question_order' => ++ $order
					),
					array( '%d', '%d', '%d' )
				);
				$this->_create_question_meta( $old_question_id, $new_question_id );
				$this->_update_question_tag( $old_question_id, $new_question_id );

				$new_questions[ $old_question_id ]       = $new_question_id;
				self::$questions_map[ $old_question_id ] = $new_question_id;

			}
		}

		return $new_questions;
	}

	private function _create_question( $old_id ) {

	}

	private function _create_question_meta( $old_id, $new_id ) {
		$keys          = array(
			'_lpr_question'      => null,
			'_lpr_question_mark' => '_lp_mark',
			'_lpr_duration'      => null
		);
		$question_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
		if ( $question_meta ) {
			foreach ( $question_meta as $meta ) {
				if ( ! $keys[ $meta['meta_key'] ] ) {
					continue;
				}
				$new_key   = $keys[ $meta['meta_key'] ];
				$new_value = $meta['meta_value'];
				add_post_meta( $new_id, $new_key, $new_value );
			}
		}

		$meta = get_post_meta( $old_id, '_lpr_question', true );
		if ( $meta ) {
			global $wpdb;
			if ( ! empty( $meta['type'] ) ) {
				add_post_meta( $new_id, '_lp_type', $meta['type'] );
			}
			if ( ! empty( $meta['answer'] ) ) {
				if ( in_array( $meta['type'], array(
					'true_or_false',
					'single_choice',
					'multi_choice',
					'sorting_choice'
				) ) ) {
					$ordering = 0;
					foreach ( $meta['answer'] as $order => $answer ) {
						$question_data = array(
							'text'    => $answer['text'],
							'value'   => $ordering,
							'is_true' => $this->_is_false_value( $answer['is_true'] ) ? 'no' : 'yes'
						);
						if ( $meta['type'] == 'sorting_choice' ) {
							unset( $question_data['is_true'] );
						}
						$wpdb->insert(
							$wpdb->prefix . 'learnpress_question_answers',
							array(
								'question_id'  => $new_id,
								'answer_data'  => serialize( $question_data ),
								'answer_order' => ++ $ordering
							),
							array( '%d', '%s', '%d' )
						);
					}
				}
			}
			if ( $meta['type'] == 'fill_in_blank' ) {
				$wpdb->insert(
					$wpdb->prefix . 'learnpress_question_answers',
					array(
						'question_id'  => $new_id,
						'answer_data'  => maybe_serialize( array( 'passage' => ! empty( $meta['passage'] ) ? $meta['passage'] : '' ) ),
						'answer_order' => 0
					),
					array( '%d', '%s', '%d' )
				);
			}
		}
		/**
		 * Update other meta data
		 */
		$this->update_post_metas(
			$old_id,
			$new_id,
			array_keys( $keys )
		);
		$this->_mark_upgraded( $old_id, $new_id );

	}

	/**
	 * @param $old_id
	 * @param $new_id
	 */
	private function _update_question_tag( $old_id, $new_id ) {
		$tags = array();
		if ( $terms = wp_get_object_terms( $old_id, array( 'question_tag', 'question-tag' ) ) ) {
			foreach ( $terms as $term ) {
				if ( $term->taxonomy == 'question-tag' ) {
					$_term = term_exists( $term->name, 'question_tag' );
					if ( $_term === 0 || $_term === null ) {
						$_term = wp_insert_term(
							$term->name, // the term
							'question_tag', // the taxonomy
							array(
								'description' => $term->description,
								'slug'        => $term->slug,
								'parent'      => $term->parent
							)
						);
					} else {
					}
					if ( ! is_wp_error( $_term ) ) {
						$tags[] = absint( $_term['term_id'] );
					}
				} else {
					$tags[] = absint( $term->term_id );
				}
			}
		}
		if ( $tags ) {
			wp_set_object_terms( $new_id, $tags, 'question_tag' );
		}
	}

	private function _upgrade_course( $old_course ) {
		$course_args              = (array) $old_course;
		$course_args['post_type'] = 'lp_course';
		unset( $course_args['ID'] );
		$new_course_id = wp_insert_post( $course_args );
		$section_items = false;
		if ( $new_course_id ) {
			$section_items = $this->_create_curriculum( $old_course->ID, $new_course_id );
			$this->_create_course_meta( $old_course->ID, $new_course_id );
			$this->_update_course_category( $old_course->ID, $new_course_id );
			$this->_update_course_tag( $old_course->ID, $new_course_id );
			$this->_update_course_thumbnail( $old_course->ID, $new_course_id );
			$this->_update_course_comments( $old_course->ID, $new_course_id );

		}

		return array( 'id' => $new_course_id, 'section_items' => $section_items );
	}

	private function _update_course_comments( $old_id, $new_id, $parent_id = 0 ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT c.*
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
			WHERE p.ID = %d
			AND p.post_type = %s
		", $old_id, 'lpr_course' );
		if ( $comments = $wpdb->get_results( $query ) ) {
			foreach ( $comments as $c ) {
				$wpdb->update(
					$wpdb->comments,
					array( 'comment_post_ID' => $new_id ),
					array( 'comment_ID' => $c->comment_ID ),
					array( '%d' )
				);
				update_comment_meta( $c->comment_ID, '_lpr_old_course', $old_id );
			}
		}
	}

	/**
	 * @param $old_id
	 * @param $new_id
	 */
	private function _update_course_category( $old_id, $new_id ) {
		$categories = array();
		if ( $terms = wp_get_object_terms( $old_id, 'course_category' ) ) {
			foreach ( $terms as $term ) {
				$categories[] = $term->term_id;
			}
		}
		if ( $categories ) {
			wp_set_object_terms( $new_id, $categories, 'course_category' );
		}
	}

	/**
	 * @param $old_id
	 * @param $new_id
	 */
	private function _update_course_tag( $old_id, $new_id ) {
		$tags = array();
		if ( $terms = wp_get_object_terms( $old_id, 'course_tag' ) ) {
			foreach ( $terms as $term ) {
				$tags[] = $term->term_id;
			}
		}
		if ( $tags ) {
			wp_set_object_terms( $new_id, $tags, 'course_tag' );
		}
	}

	/**
	 * @param $old_id
	 * @param $new_id
	 */
	private function _update_course_thumbnail( $old_id, $new_id ) {
		if ( $post_thumbnail_id = get_post_thumbnail_id( $old_id ) ) {
			set_post_thumbnail( $new_id, $post_thumbnail_id );
		}
	}

	private function _upgrade_courses( $force = false ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT DISTINCT p.*, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type = %s
			" . ( ! $force ? "HAVING upgraded IS NULL" : "" ) . "
		", '_learn_press_upgraded', 'lpr_course' );

		$new_courses = array();

		if ( $old_courses = $wpdb->get_results( $query ) ) {
			foreach ( $old_courses as $old_course ) {
				$return                         = $this->_upgrade_course( $old_course );
				$new_courses[ $old_course->ID ] = $return;
				if ( $return['id'] ) {
					$this->_mark_upgraded( $old_course->ID, $return['id'] );
				}
			}
		}

		self::$courses_map = $new_courses;
		$posts             = array();
		foreach ( $new_courses as $c ) {
			$posts[] = $c['id'];
			if ( $c['section_items'] ) {
				foreach ( $c['section_items'] as $si ) {
					$posts[] = $si['id'];
					if ( ! empty( $si['questions'] ) ) {
						$posts = array_merge( $posts, $si['questions'] );
					}
				}
			}
		}

//		$this->_remove_unused_data();
	}

	private function _remove_unused_data() {
		global $wpdb;
		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->postmeta}
			USING {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID={$wpdb->postmeta}.post_id
			WHERE
			{$wpdb->postmeta}.meta_key LIKE %s
			AND {$wpdb->posts}.post_type LIKE %s", '\_lpr\_%', 'lp\_%' );
		$wpdb->query( $query );
	}

	public function get_posts_by_ids( $ids, $types = array() ) {
		global $wpdb;
		settype( $ids, 'array' );
		$query = "SELECT * FROM {$wpdb->posts} WHERE ID IN(" . join( ',', $ids ) . ")";
		if ( $types ) {
			settype( $types, 'array' );
			$query .= " AND post_type IN('" . join( "','", $types ) . "')";
		}
		$posts = array();
		if ( $rows = $wpdb->get_results( $query, OBJECT_K ) ) {
			foreach ( $ids as $id ) {
				if ( ! empty( $rows[ $id ] ) ) {
					$posts[ $id ] = (array) $rows[ $id ];
				}
			}
		}

		return $posts;
	}

	public function get_post_meta( $post_id, $keys ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT pm.*
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key in ('" . join( "','", $keys ) . "')
			AND pm.post_id = %d
		", absint( $post_id ) );
		$metas = (array) $wpdb->get_results( $query, ARRAY_A );

		return $metas;
	}

	public function update_post_metas( $old_id, $new_id, $exclude = null ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			INSERT INTO {$wpdb->postmeta}(post_id, meta_key, meta_value)
			SELECT %d as post_id, pm.meta_key, pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE 1
				" . ( is_array( $exclude ) ? "AND pm.meta_key NOT IN('" . join( "','", $exclude ) . "')" : "" ) . "
				AND pm.post_id = %d
		", $new_id, $old_id );
		$wpdb->query( $query );
	}

	private function rollback_database() {
		global $wpdb;
		$query = "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type IN('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_order' )
		";
		if ( $ids = $wpdb->get_col( $query ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN(" . join( ",", $ids ) . ")" );
			$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN(" . join( ",", $ids ) . ")" );

			$table = $wpdb->prefix . 'learnpress_sections';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$wpdb->query( "DELETE FROM {$wpdb->learnpress_sections}" );
			}
			$table = $wpdb->prefix . 'learnpress_section_items';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$wpdb->query( "DELETE FROM {$wpdb->learnpress_section_items}" );
			}
			$table = $wpdb->prefix . 'learnpress_quiz_history';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$wpdb->query( "DELETE FROM {$wpdb->learnpress_quiz_history}" );
			}
			$table = $wpdb->prefix . 'learnpress_user_course';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$wpdb->query( "DELETE FROM {$wpdb->learnpress_user_course}" );
			}
		}
		delete_option( 'learnpress_db_version' );
		die();
	}

	private function _upgrade_orders() {
		global $wpdb;
		$query  = $wpdb->prepare( "
			SELECT p.*, u.ID as user_id
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->usermeta} um ON um.meta_value = p.ID AND um.meta_key = %s
			INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
		", '_lpr_order_id' );
		$orders = $wpdb->get_results( $query );
		if ( ! $orders ) {
			return;
		}

		foreach ( $orders as $order ) {
			$order_data              = (array) $order;
			$order_data['post_type'] = LP_ORDER_CPT;
			unset( $order_data['ID'] );
			$old_order_id = $order->ID;
			if ( $new_order_id = wp_insert_post( $order_data ) ) {
				$this->_create_order_meta( $old_order_id, $new_order_id );
			}
			self::$orders_map[ $old_order_id ] = $new_order_id;
		}
	}

	private function _create_order_meta( $old_id, $new_id ) {
		global $wpdb;
		$keys       = array(
			'_learn_press_transaction_method'    => '_payment_method',
			'_learn_press_customer_id'           => '_user_id',
			'_learn_press_customer_ip'           => '_user_ip_address',
			'_learn_press_order_items'           => null,
			'_learn_press_transaction_method_id' => '_transaction_id'
		);
		$order_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
		if ( $order_meta ) {
			foreach ( $order_meta as $meta ) {
				if ( '_learn_press_order_items' == $meta['meta_key'] ) {
					$order_data = LP_Helper::maybe_unserialize( $meta['meta_value'] );
					if ( isset( $order_data->total ) ) {
						add_post_meta( $new_id, '_order_total', $order_data->total );
					} else {
						add_post_meta( $new_id, '_order_total', 0 );
					}

					if ( isset( $order_data->sub_total ) ) {
						add_post_meta( $new_id, '_order_subtotal', $order_data->sub_total );
					} else {
						add_post_meta( $new_id, '_order_subtotal', 0 );
					}

					if ( isset( $order_data->currency ) ) {
						add_post_meta( $new_id, '_order_currency', $order_data->currency );
					} else {
						add_post_meta( $new_id, '', 'USD' );
					}

					if ( isset( $order_data->products ) ) {
						foreach ( $order_data->products as $order_item ) {
							$old_course_id = $order_item['id'];
							$new_course_id = isset( self::$courses_map[ $old_course_id ] ) ? self::$courses_map[ $old_course_id ]['id'] : 0;
							if ( $new_course_id ) {
								$new_course    = get_post( $new_course_id );
								$r             = $wpdb->insert(
									$wpdb->prefix . 'learnpress_order_items',
									array(
										'order_item_name' => isset( $order_item['product_name'] ) ? $order_item['product_name'] : $new_course->post_title,
										'order_id'        => $new_id
									)
								);
								$order_item_id = $wpdb->insert_id;
								learn_press_add_order_item_meta( $order_item_id, '_course_id', $new_course->ID );
								learn_press_add_order_item_meta( $order_item_id, '_quantity', $order_item['quantity'] );
								learn_press_add_order_item_meta( $order_item_id, '_subtotal', $order_item['product_subtotal'] );
								learn_press_add_order_item_meta( $order_item_id, '_total', $order_item['product_subtotal'] );
							}
							if ( empty( self::$course_order_map[ $old_course_id ] ) ) {
								self::$course_order_map[ $old_course_id ] = array();
							}
							self::$course_order_map[ $old_course_id ][] = $old_id;
						}
					}

					add_post_meta( $new_id, '_prices_include_tax', 'no' );
					add_post_meta( $new_id, '_user_agent', '' );
					add_post_meta( $new_id, '_order_key', '' );
					add_post_meta( $new_id, '_transaction_fee', '0' );

					continue;
				}
				$new_key   = $keys[ $meta['meta_key'] ];
				$new_value = $meta['meta_value'];
				if ( '_payment_method' == $new_key ) {
					$method_title = preg_replace( '!-!', ' ', $new_value );
					$method_title = ucwords( $method_title );
					add_post_meta( $new_id, '_payment_method_title', $method_title );
				}

				add_post_meta( $new_id, $new_key, $new_value );
			}
		}

		/**
		 * Update other meta data
		 */
		$this->update_post_metas(
			$old_id,
			$new_id,
			array_keys( $keys )
		);
		$this->_mark_upgraded( $old_id, $new_id );
	}

	private function _upgrade_order_courses() {
		global $wpdb;
		$user_meta_keys = array(
			'_lpr_user_course',
			'_lpr_course_time',
			'_lpr_quiz_start_time',
			'_lpr_quiz_questions',
			'_lpr_quiz_current_question',
			'_lpr_quiz_question_answer',
			'_lpr_quiz_completed',
			'_lpr_lesson_completed'
		);
		$fields         = array();
		$join           = array();
		$having         = array();
		$index          = 2;
		foreach ( $user_meta_keys as $key ) {
			$new_key  = preg_replace( '!_lpr_!', '', $key );
			$fields[] = sprintf( "T{$index}.meta_value AS %s", $new_key );
			$join[]   = $wpdb->prepare( "LEFT JOIN {$wpdb->usermeta} T{$index} ON T{$index}.user_id = T1.user_id AND T{$index}.meta_key = %s", $key );
			$having[] = $new_key . ' IS NOT NULL';
			$index ++;
		}
		$query          = sprintf( "
			SELECT distinct T1.user_id,
				%s
			FROM {$wpdb->usermeta} AS T1
				%s
			HAVING (
				%s
			)", join( ",\n", $fields ), join( "\n", $join ), join( "\nOR ", $having ) );
		$user_meta_rows = $wpdb->get_results( $query );
		if ( ! $user_meta_rows ) {
			return;
		}
		foreach ( $user_meta_rows as $user_meta ) {
			$user_meta = $this->_parse_user_meta( $user_meta );
			$new_course_id     = 0;
			$user_course_items = array();
			$user_parent_items = array();
			if ( ! empty( $user_meta->user_course ) && ! empty( $user_meta->course_time ) ) {
				$user_meta->user_course = array_unique( $user_meta->user_course );
				foreach ( $user_meta->user_course as $course_id ) {
					if ( ! empty( self::$courses_map[ $course_id ] ) && ! empty( $user_meta->course_time[ $course_id ] ) ) {
						$new_course_id   = self::$courses_map[ $course_id ]['id'];
						$course_time     = $user_meta->course_time[ $course_id ];
						$course_end_time = ! empty( $course_time['end'] ) ? $course_time['end'] : '';
						if ( ! empty( self::$course_order_map[ $course_id ] ) ) {
							$course_order = reset( self::$course_order_map[ $course_id ] );
						} else {
							$course_order = 0;
						}
						$user                            = learn_press_get_user( $user_meta->user_id );
						$user_item_id                    = learn_press_update_user_item_field( array(
							'user_id'    => $user_meta->user_id,
							'status'     => $course_end_time ? 'completed' : 'started',
							'start_time' => isset( $course_time['start'] ) ? date( 'Y-m-d H:i:s', $course_time['start'] ) : '0000-00-00 00:00:00',
							'end_time'   => $course_end_time ? date( 'Y-m-d H:i:s', $course_end_time ) : '0000-00-00 00:00:00',
							'item_id'    => $new_course_id,
							'item_type'  => LP_COURSE_CPT,
							'ref_id'     => $course_order,
							'ref_type'   => LP_ORDER_CPT,
							'parent_id'  => 0
						) );
						$user_course_items[ $course_id ] = $user_item_id;
						if ( ! empty( self::$courses_map[ $course_id ]['section_items'] ) ) {
							foreach ( self::$courses_map[ $course_id ]['section_items'] as $old_item_id => $new ) {
								$user_parent_items[ $old_item_id ] = $course_id;
							}
						}
					}
				}
			}
			$user_lesson_items = array();
			if ( ! empty( $user_meta->lesson_completed ) ) {
				foreach ( $user_meta->lesson_completed as $old_course_id => $_lessons ) {
					$lesson_start_time = null;
					$lesson_end_time   = null;
					if ( ! empty( $user_meta->course_time ) && ! empty( $user_meta->course_time[ $old_course_id ] ) ) {
						$lesson_start_time = ! empty( $user_meta->course_time[ $old_course_id ]['start'] ) ? $user_meta->course_time[ $old_course_id ]['start'] : '';
						$lesson_end_time   = ! empty( $user_meta->course_time[ $old_course_id ]['end'] ) ? $user_meta->course_time[ $old_course_id ]['end'] : '';
					}
					if ( ! empty( self::$courses_map[ $old_course_id ] ) ) {
						$new_course_id = self::$courses_map[ $old_course_id ]['id'];
					}
					if ( $_lessons ) {
						foreach ( $_lessons as $old_lesson_id ) {
							$item_id                             = ! empty( self::$lessons_map[ $old_lesson_id ] ) ? self::$lessons_map[ $old_lesson_id ] : '';
							$user_item_id                        = learn_press_update_user_item_field( array(
								'status'     => $lesson_end_time ? 'completed' : 'started',
								'start_time' => $lesson_start_time ? date( 'Y-m-d H:i:s', $lesson_start_time ) : '0000-00-00 00:00:00',
								'end_time'   => $lesson_end_time ? date( 'Y-m-d H:i:s', $lesson_end_time ) : '0000-00-00 00:00:00',
								'item_id'    => ! empty( self::$lessons_map[ $old_lesson_id ] ) ? self::$lessons_map[ $old_lesson_id ] : '',
								'user_id'    => $user_meta->user_id,
								'item_type'  => LP_LESSON_CPT,
								'ref_id'     => $new_course_id,
								'ref_type'   => LP_COURSE_CPT,
								'parent_id'  => isset( $user_course_items[ $old_course_id ] ) ? $user_course_items[ $old_course_id ] : 0
							) );
							$user_lesson_items[ $old_lesson_id ] = $user_item_id;
						}
					}
				}
			}
			if ( ! empty( $user_meta->quiz_start_time ) ) {
				foreach ( $user_meta->quiz_start_time as $old_quiz_id => $time ) {
					if ( empty( self::$quizzes_map[ $old_quiz_id ] ) ) {
						continue;
					}
					if ( ! isset( $user_parent_items[ $old_quiz_id ] ) ) {
					}
					$item_id       = ! empty( self::$quizzes_map[ $old_quiz_id ] ) ? self::$quizzes_map[ $old_quiz_id ] : '';
					$old_course_id = ! empty( $user_parent_items[ $old_quiz_id ] ) ? $user_parent_items[ $old_quiz_id ] : 0;
					$new_course_id = ! empty( self::$courses_map[ $old_course_id ] ) ? self::$courses_map[ $old_course_id ]['id'] : 0;
					$user_quiz_id  = learn_press_update_user_item_field( array(
						'status'     => ! empty( $user_meta->quiz_completed[ $old_quiz_id ] ) ? 'completed' : 'started',
						'start_time' => date( 'Y-m-d H:i:s', $time ),
						'end_time'   => ! empty( $user_meta->quiz_completed[ $old_quiz_id ] ) ? date( 'Y-m-d H:i:s', $user_meta->quiz_completed[ $old_quiz_id ] ) : '0000-00-00 00:00:00',
						'user_id'    => $user_meta->user_id,
						'item_id'    => $item_id,
						'item_type'  => LP_QUIZ_CPT,
						'ref_id'     => $new_course_id,
						'ref_type'   => LP_COURSE_CPT,
						'parent_id'  => isset( $user_course_items[ $old_course_id ] ) ? $user_course_items[ $old_course_id ] : 0
					) );

					if ( ! empty( $user_meta->quiz_current_question ) ) {
						if ( ! empty( $user_meta->quiz_current_question[ $old_quiz_id ] ) ) {
							learn_press_update_user_item_meta( $user_quiz_id, 'current_question', self::$questions_map[ $user_meta->quiz_current_question[ $old_quiz_id ] ] );
						}
					}
					if ( ! empty( $user_meta->quiz_question_answer ) ) {
						if ( ! empty( $user_meta->quiz_question_answer[ $old_quiz_id ] ) ) {
							$question_answers = array();
							foreach ( $user_meta->quiz_question_answer[ $old_quiz_id ] as $old_question_id => $answer ) {
								if ( ! empty( self::$questions_map[ $old_question_id ] ) ) {
									$question_answers[ self::$questions_map[ $old_question_id ] ] = $answer;
								}
							}
							learn_press_update_user_item_meta( $user_quiz_id, 'question_answers', $question_answers );
						}
					}
					if ( ! empty( $user_meta->quiz_questions ) ) {
						if ( ! empty( $user_meta->quiz_questions[ $old_quiz_id ] ) ) {
							$quiz_questions = array();
							foreach ( $user_meta->quiz_questions[ $old_quiz_id ] as $old_question_id ) {
								if ( ! empty( self::$questions_map[ $old_question_id ] ) ) {
									$quiz_questions[] = self::$questions_map[ $old_question_id ];
								}
							}
							learn_press_update_user_item_meta( $user_quiz_id, 'question_answers', $quiz_questions );
						}
					}
				}
			}
		}
	}

	private function _get_course_order_by_user( $user, $course ) {
		global $wpdb;
		$query = "
		";
	}

	private function _parse_user_meta( $meta ) {
		$origin_type = gettype( $meta );
		$meta        = (array) $meta;
		foreach ( $meta as $k => $v ) {
			$meta[ $k ] = LP_Helper::maybe_unserialize( $v );
		}
		settype( $meta, $origin_type );

		return $meta;
	}

	private function _upgrade_user_roles() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT um.*
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
			WHERE um.meta_value LIKE %s
		", 'wp_capabilities', '%"lpr\_teacher"%' );
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				$user = new WP_User( $row->user_id );
				$user->remove_role( 'lpr_teacher' );
				$user->add_role( 'lp_teacher' );
			}
		}
		remove_role( 'lpr_teacher' );
	}

	private function _update_user_lessons() {

	}

	/**
	 * Register old taxonomy so we can use wp_get_object_terms on old post types
	 */
	private function _backward_compatible() {
		register_taxonomy( 'lesson-tag', array( 'lpr_lesson', LP_LESSON_CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Tag', 'learnpress' ),
					'menu_name'     => __( 'Tag', 'learnpress' ),
					'singular_name' => __( 'Tag', 'learnpress' ),
					'add_new_item'  => __( 'Add New Tag', 'learnpress' ),
					'all_items'     => __( 'All Tags', 'learnpress' )
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => 'true',
				'show_in_nav_menus' => true,
				'rewrite'           => array(
					'slug'         => _x( 'lesson-tag', 'Permalink Slug', 'learnpress' ),
					'hierarchical' => true,
					'with_front'   => false
				),
			)
		);

		register_taxonomy( 'question-tag', array( 'lpr_question', LP_QUESTION_CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Question Tag', 'learnpress' ),
					'menu_name'     => __( 'Tag', 'learnpress' ),
					'singular_name' => __( 'Tag', 'learnpress' ),
					'add_new_item'  => __( 'Add New Tag', 'learnpress' ),
					'all_items'     => __( 'All Tags', 'learnpress' )
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => 'true',
				'show_in_nav_menus' => true,
				'rewrite'           => array(
					'slug'         => _x( 'question-tag', 'Permalink Slug', 'learnpress' ),
					'hierarchical' => false,
					'with_front'   => false
				),
			)
		);

		add_post_type_support( 'lpr_lesson', 'post-formats' );
	}

	// Update settings
	public function upgrade_settings() {
		// update general settings
		if ( $_lpr_settings_general = get_option( '_lpr_settings_general' ) ) {
			$options = explode( ' ', 'currency currency_pos thousands_separator decimals_separator number_of_decimals instructor_registration' );
			foreach ( $options as $o ) {
				if ( array_key_exists( $o, $_lpr_settings_general ) ) {
					add_option( 'learn_press_' . $o, $_lpr_settings_general[ $o ] );
				}
			}
		}

		// update payments settings
		if ( $payments = get_option( '_lpr_settings_payment' ) ) {
			foreach ( $payments as $payment => $options ) {
				if ( $payment == 'paypal' ) {
					if ( ! empty( $options['enable'] ) && $options['enable'] == 'on' ) {
						add_option( 'learn_press_paypal_enable', 'yes' );
					} else {
						add_option( 'learn_press_paypal_enable', 'no' );
					}
					add_option( 'learn_press_paypal_email', ! empty( $options['paypal_email'] ) ? $options['paypal_email'] : '' );
					add_option( 'learn_press_paypal_sandbox_email', ! empty( $options['paypal_sandbox_email'] ) ? $options['paypal_sandbox_email'] : '' );
					add_option( 'learn_press_paypal_sandbox', ! empty( $options['sandbox'] ) ? 'yes' : 'no' );
				}
			}
		}

		// update emails
		if ( $_lpr_settings_emails = get_option( '_lpr_settings_emails' ) ) {
			$emails = array(
				'published_course' => array(
					'learn_press_emails_published_course',
					'published-course'
				),
				'enrolled_course'  => array(
					'learn_press_emails_enrolled_course',
					'enrolled-course'
				),
				'passed_course'    => array(
					'learn_press_emails_finished_course',
					'finished-course'
				)
			);

			foreach ( $_lpr_settings_emails as $email_type => $email ) {
				if ( in_array( $email_type, array( 'published_course', 'enrolled_course', 'passed_course' ) ) ) {
					$new_email = wp_parse_args(
						get_option( $emails[ $email_type ][0] ),
						array(
							'enable'       => '',
							'subject'      => '',
							'messages'     => '',
							'email_format' => ''
						)
					);

					$new_email['enable']  = ! empty( $email['enable'] ) ? 'yes' : 'no';
					$new_email['subject'] = ! empty( $email['subject'] ) ? $email['subject'] : $new_email['subject'];
					if ( ! empty( $email['message'] ) ) {

						$templates = array( 'learnpress' );
						if ( get_template() == 'eduma' ) {
							$templates[] = 'learnpress-v1';
						}
						foreach ( $templates as $_template ) {
							if ( $new_email['email_format'] == 'html' ) {
								$template_path = get_template_directory() . '/' . $_template . '/emails/';
							} else {
								$template_path = get_template_directory() . '/' . $_template . '/emails/plain/';
							}
							if ( wp_mkdir_p( $template_path ) && ! file_exists( $template_path . $emails[ $email_type ][1] . '.php' ) ) {
								if ( $file_handle = @fopen( $template_path . $emails[ $email_type ][1] . '.php', 'w' ) ) {
									fwrite( $file_handle, $email['message'] );
									fclose( $file_handle );
								}
							}
						}
					}
					add_option( $emails[ $email_type ][0], $new_email );
				} elseif ( $email_type == 'general' ) {
					$new_email               = wp_parse_args(
						get_option( 'learn_press_emails_general' ),
						array(
							'from_name'  => '',
							'from_email' => ''
						)
					);
					$new_email['from_name']  = ! empty( $email['from_name'] ) ? $email['from_name'] : $new_email['from_name'];
					$new_email['from_email'] = ! empty( $email['from_email'] ) ? $email['from_email'] : $new_email['from_email'];
					add_option( 'learn_press_emails_general', $new_email );
				}
			}
		}
	}

	private function _mark_upgraded( $old, $new ) {
		update_post_meta( $old, '_learn_press_upgraded', $new );
		update_post_meta( $new, '_learn_press_upgraded_from', $old );
	}

	public function upgrade_database() {
		LP_Install::update();
	}

	public function do_upgrade() {
		global $wpdb;
		set_time_limit( 0 );
		// start a transaction so we can rollback all as begin if there is an error
		$wpdb->query( "START TRANSACTION;" );
		$force = learn_press_get_request( 'force' ) == 'true';
		try {
			$this->_backward_compatible();
			// update courses
			$this->_upgrade_courses( $force );
			// update unassigned items
			$this->_upgrade_unassigned_items( $force );
			// update unassigned questions
			$this->_upgrade_unassigned_questions( $force );
			// update orders
			$this->_upgrade_orders();
			// orders
			$this->_upgrade_order_courses();
			// user roles
			$this->_upgrade_user_roles();
			// settings
			$this->upgrade_settings();
			// update database
			$this->upgrade_database();
		}
		catch ( Exception $ex ) {
			$wpdb->query( "ROLLBACK;" );
			wp_die( $ex->getMessage() );
		}
		$wpdb->query( "COMMIT;" );
		update_option( 'learnpress_version', LP()->version );
		update_option( 'learnpress_db_version', LP()->version );
		update_option( '_learn_press_flush_rewrite_rules', 'yes' );
		// cui bap
		update_option( 'permalink_structure', '/%postname%/' );
		delete_transient( 'learn_press_is_old_version' );

		learn_press_update_log( '1.0.x', array( 'time' => time() ) );

		return true;
	}

	/**
	 * Display update page content
	 */
	public function learn_press_upgrade_10_page() {
		if ( empty( $_REQUEST['page'] ) || $_REQUEST['page'] != 'learn_press_upgrade_from_09' ) {
			return;
		}
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'learn-press-upgrade-09' ) ) {
			wp_redirect( admin_url() );
			exit();
		}
		if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'upgrade' ) {
			if ( $this->do_upgrade() ) {
				$_REQUEST['step'] = 'upgraded';
			}
		}

		wp_enqueue_style( 'learn-press-upgrade-x', LP()->plugin_url( 'inc/updates/09/style.css' ), array(
			'dashicons',
			'install'
		) );
		wp_enqueue_script( 'learn-press-upgrade-x', LP()->plugin_url( 'inc/updates/09/script.js' ), array( 'jquery' ) );

		add_action( 'learn_press_update_step_welcome', array( $this, 'update_welcome' ) );
		add_action( 'learn_press_update_step_upgraded', array( $this, 'update_upgraded' ) );

		$step = ! empty( $_REQUEST['step'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['step'] ) ) : 'welcome';
		if ( ! in_array( $step, $this->_steps ) ) {
			$step = reset( $this->_steps );
		}
		$this->_current_step = $step;
		$view                = learn_press_get_admin_view( 'updates/0.9/update-wizard.php' );
		include_once $view;
		exit();
	}

	/**
	 * Add menu to make it work properly
	 */
	public function learn_press_update_10_menu() {
		add_dashboard_page( '', '', 'manage_options', 'learn_press_upgrade_from_09', '' );
	}

	/**
	 * Welcome step page
	 */
	public function update_welcome() {
		$view = learn_press_get_admin_view( 'updates/0.9/step-welcome.php' );
		include $view;
	}

	public function update_upgraded() {
		$view = learn_press_get_admin_view( 'updates/0.9/step-upgraded.php' );
		include $view;
	}

	/**
	 * Repair Database step page
	 */
	public function update_repair_database() {
		$view = learn_press_get_admin_view( 'updates/0.9/step-repair-database.php' );
		include $view;
	}

	public function next_link() {
		if ( $this->_current_step ) {
			if ( ( $pos = array_search( $this->_current_step, $this->_steps ) ) !== false ) {
				if ( $pos < sizeof( $this->_steps ) - 1 ) {
					$pos ++;

					return admin_url( 'admin.php?page=learn_press_upgrade_from_09&step=' . $this->_steps[ $pos ] );
				}
			}
		}

		return false;
	}

	public function prev_link() {
		if ( $this->_current_step ) {
			if ( ( $pos = array_search( $this->_current_step, $this->_steps ) ) !== false ) {
				if ( $pos > 0 ) {
					$pos --;

					return admin_url( 'admin.php?page=learn_press_upgrade_from_09&step=' . $this->_steps[ $pos ] );
				}
			}
		}

		return false;
	}
}

new LP_Upgrade_From_09();