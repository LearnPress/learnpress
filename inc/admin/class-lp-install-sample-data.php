<?php

/**
 * Class LP_Install_Sample_Data
 *
 * Create sample course for testing purpose.
 * This will create sections, items, questions, answers, ...
 *
 * @since 3.0.0
 */
class LP_Install_Sample_Data {

	/**
	 * @var array
	 */
	public static $section_range = array( 5, 10 );

	/**
	 * @var array
	 */
	public static $item_range = array( 10, 15 );

	/**
	 * @var array
	 */
	public static $question_range = array( 10, 15 );

	/**
	 * @var array
	 */
	public static $answer_range = array( 3, 5 );

	/**
	 * @var int
	 */
	public static $max_content_paragraph = 5;

	/**
	 * @var string
	 */
	protected $dummy_text = '';

	/**
	 * LP_Install_Sample_Data constructor.
	 */
	public function __construct() {
		add_filter( 'learn-press/script-data', array( $this, 'i18n' ), 10, 2 );

		$actions = array(
			'lp-install-sample-data',
			'lp-uninstall-sample-data',
		);

		if ( ! in_array( LP_Request::get( 'page' ), $actions ) ) {
			return;
		}

		add_action( 'init', array( $this, 'install' ) );
		add_action( 'init', array( $this, 'uninstall' ) );
	}

	public function i18n( $data, $handle ) {
		if ( 'learn-press-global' !== $handle ) {
			return $data;
		}

		$i18n = array(
			'confirm_install_sample_data'   => esc_html__( 'Are you sure you want to install sample course data?', 'learnpress' ),
			'confirm_uninstall_sample_data' => esc_html__( 'Are you sure you want to delete sample course data?', 'learnpress' ),
		);

		if ( empty( $data['i18n'] ) ) {
			$data['i18n'] = $i18n;
		} else {
			$data['i18n'] = array_merge( $data['i18n'], $i18n );
		}

		return $data;
	}

	/**
	 * Install
	 */
	public function install() {
		if ( ! wp_verify_nonce( LP_Request::get_string( '_wpnonce' ), 'install-sample-course' ) ) {
			return;
		}

		$dummy_text       = LP_WP_Filesystem::instance()->file_get_contents( LP_PLUGIN_PATH . '/dummy-data/dummy-text.txt' );
		$this->dummy_text = preg_split( '!\s!', $dummy_text );

		$section_range = LP_Request::get( 'section-range' );
		if ( $section_range ) {
			self::$section_range = $section_range;
		}

		$item_range = LP_Request::get( 'item-range' );
		if ( $item_range ) {
			self::$item_range = $item_range;
		}

		$question_range = LP_Request::get( 'question-range' );
		if ( $question_range ) {
			self::$question_range = $question_range;
		}

		$answer_range = LP_Request::get( 'answer-range' );
		if ( $answer_range ) {
			self::$answer_range = $answer_range;
		}
		//LP_Debug::startTransaction();

		try {
			@ini_set( 'memory_limit', '2G' );

			global $wp_filter;

			$keys        = array_keys( $wp_filter );
			$ignore_keys = array( 'sanitize_title' );

			foreach ( $keys as $key ) {
				if ( in_array( $key, $ignore_keys ) ) {
					continue;
				}

				unset( $wp_filter[ $key ] );
			}

			$name      = LP_Request::get_string( 'custom-name' );
			$course_id = $this->create_course( $name );

			if ( ! $course_id ) {
				throw new Exception( 'Create course failed.' );
			}

			$hooks = LP_Helper::sanitize_params_submitted( $_REQUEST['hooks'] ?? array() );

			if ( ! empty( $hooks ) ) {
				$hooks = (array) json_decode( $hooks );

				/*$wpml_settings = array();
				global $wpdb;
				$wpml = new WPML_Admin_Post_Actions( $wpml_settings, $wpdb );
				$wpml->save_post_actions( $course_id, get_post( $course_id ) );*/

				$args = array();
				if ( ! empty( $hooks ) ) {
					foreach ( $hooks as $hook ) {
						if ( isset( $hook->class ) && isset( $hook->action ) ) {
							$class_name  = $hook->class;
							$action_name = $hook->action;

							switch ( $action_name ) {
								case 'save_post':
									$args = $course_id;
									break;
								default:
									break;
							}

							if ( is_callable( array( $class_name, $action_name ) ) ) {
								call_user_func( array( $class_name, $action_name ), $args );
							}
						}
					}
				}
			}

			$this->create_sections( $course_id );

			$price = LP_Request::get( 'course-price' );

			if ( $price ) {
				update_post_meta( $course_id, '_lp_regular_price', $price );
			}

			/**
			 * Save info course in background.
			 */
			$bg = LP_Background_Single_Course::instance();
			$bg->data(
				array(
					'handle_name' => 'save_post',
					'course_id'   => $course_id,
				)
			)->dispatch();
			?>

			<div class="lp-install-sample__response success">
				<?php printf( __( 'Course "%s" has been created', 'learnpress' ), get_the_title( $course_id ) ); ?>
				<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'learnpress' ); ?></a>
				|
				<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $course_id . '&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'learnpress' ); ?></a>
			</div>

			<?php
			//LP_Debug::commitTransaction();

		} catch ( Exception $ex ) {
			//LP_Debug::rollbackTransaction();
			echo '<div class="lp-install-sample__response fail">';
			echo $ex->getMessage();
			echo '</div>';
		}

		die();
	}

	/**
	 * Un-install
	 */
	public function uninstall() {
		if ( ! wp_verify_nonce( LP_Request::get_string( '_wpnonce' ), 'uninstall-sample-course' ) ) {
			return;
		}

		global $wpdb;

		$posts = $this->get_sample_posts();
		try {
			if ( ! $posts ) {
				throw new Exception( esc_html__( 'No data sample.', 'learnpress' ) );
			}

			foreach ( $posts as $post ) {
				switch ( $post->post_type ) {
					case LP_COURSE_CPT:
						$this->_delete_course( $post->ID );
						break;
					case LP_QUIZ_CPT:
						$this->_delete_quiz( $post->ID );
						break;
					case LP_QUESTION_CPT:
						$this->_delete_question( $post->ID );
						break;
				}

				$this->_delete_post( $post->ID );
			}
			?>

			<div class="lp-install-sample__response success">
				<?php esc_html_e( 'Delete sample data successfully!', 'learnpress' ); ?>
			</div>

			<?php
		} catch ( Exception $ex ) {

			echo '<div class="lp-install-sample__response fail">';
			echo 'Error: ' . $ex->getMessage();
			echo '</div>';
		}

		die();
	}

	/**
	 * Get all posts marked as "sample data"
	 *
	 * @return array|null|object
	 */
	public function get_sample_posts() {
		global $wpdb;

		$query = $wpdb->prepare(
			"
	        SELECT p.ID, post_type
	        FROM {$wpdb->posts} p
	        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %s
	    ",
			'_lp_sample_data',
			'yes'
		);

		return $wpdb->get_results( $query );
	}

	protected function _delete_course( $id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
	        SELECT section_id
	        FROM {$wpdb->learnpress_sections}
	        WHERE section_course_id = %d
	    ",
			$id
		);

		$section_ids = $wpdb->get_col( $query );
		if ( $section_ids ) {
			$wpdb->query( "DELETE FROM {$wpdb->learnpress_section_items} WHERE section_id IN(" . join( ',', $section_ids ) . ')' );
			$wpdb->query( "DELETE FROM {$wpdb->learnpress_sections} WHERE section_id IN(" . join( ',', $section_ids ) . ')' );
		}
	}

	protected function _delete_quiz( $id ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->learnpress_quiz_questions} WHERE quiz_id = {$id}" );
	}

	protected function _delete_question( $id ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->learnpress_question_answers} WHERE question_id = {$id}" );
	}

	protected function _delete_post( $post_id ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id = $post_id" );
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID = $post_id" );
	}

	protected function _delete_user_items( $ids ) {
		global $wpdb;
		$format = array_fill( 0, sizeof( $ids ), '%d' );
		$query  = $wpdb->prepare(
			"
	        DELETE
	        FROM {$wpdb->learnpress_user_items}
	        WHERE item_id IN(" . join( ',', $format ) . ')
	    ',
			$ids
		);

		$wpdb->query( $query );
	}

	/**
	 * Generate content with 'lorem' text.
	 *
	 * @param int $min
	 * @param int $max
	 * @param int $paragraphs
	 *
	 * @return string
	 */
	protected function generate_content( $min = 100, $max = 500, $paragraphs = 10 ) {
		$length = rand( $min, $max );
		$max    = sizeof( $this->dummy_text ) - 1;
		$words  = array();

		for ( $i = 0; $i < $length; $i ++ ) {
			$words[] = $this->dummy_text[ rand( 0, $max ) ];
		}

		$p = array();

		if ( ! $paragraphs ) {
			$paragraphs = self::$max_content_paragraph;
		}

		while ( $words && sizeof( $p ) < $paragraphs ) {
			$len = rand( 10, 20 );
			$cut = array_splice( $words, 0, $len );
			$p[] = '<p>' . ucfirst( join( ' ', $cut ) ) . '</p>';
		}

		return join( '', $p );
	}

	/**
	 * Generate title with 'lorem' text.
	 *
	 * @param int $min
	 * @param int $max
	 *
	 * @return string
	 */
	protected function generate_title( $min = 10, $max = 15 ) {
		$length = rand( $min, $max );
		$max    = sizeof( $this->dummy_text ) - 1;
		$words  = array();
		for ( $i = 0; $i < $length; $i ++ ) {
			$words[] = $this->dummy_text[ rand( 0, $max ) ];
		}

		return ucfirst( join( ' ', $words ) );
	}

	/**
	 * Create course.
	 *
	 * @param string $name
	 *
	 * @return int|WP_Error
	 */
	protected function create_course( $name = '' ) {

		$data = array(
			'post_title'   => strlen( $name ) ? $name : __( 'Sample course', 'learnpress' ),
			'post_type'    => LP_COURSE_CPT,
			'post_status'  => 'publish',
			'post_content' => $this->generate_content( 25, 40, 5 ),
		);

		$course_id = wp_insert_post( $data );

		if ( $course_id ) {
			$metas = array(
				'_lp_duration'          => '10 week',
				'_lp_max_students'      => '1000',
				'_lp_students'          => '0',
				'_lp_retake_count'      => '0',
				'_lp_featured'          => 'no',
				'_lp_has_finish'        => 'yes',
				'_lp_course_result'     => 'evaluate_lesson',
				'_lp_passing_condition' => '80',
				'_lp_sample_data'       => 'yes',
			);
			foreach ( $metas as $key => $value ) {
				update_post_meta( $course_id, $key, $value );
			}

			$this->add_extra_info( $course_id );
		}

		return $course_id;
	}

	protected function add_extra_info( $course_id ) {
		$features = array( 'requirements', 'target_audiences', 'key_features' );

		// Requirements, Target audiences, Key Features
		foreach ( $features as $feature ) {
			$feature_data = array();
			for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i ++ ) {
				$feature_data[] = $this->generate_title();
			}
			update_post_meta( $course_id, '_lp_' . $feature, $feature_data );
		}

		// FAQs
		$feature_data = array();
		for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i ++ ) {
			$feature_data[] = array( $this->generate_title() . '?', $this->generate_content( 20, 30, 3 ) );
		}
		update_post_meta( $course_id, '_lp_faqs', $feature_data );

		// Featured review
		update_post_meta( $course_id, '_lp_featured_review', $this->generate_title( 30, 40 ) );
	}

	/**
	 * Create sections.
	 *
	 * @param int $course_id
	 */
	protected function create_sections( $course_id ) {

		$section_length = call_user_func_array( 'rand', ( self::$section_range ) );

		for ( $i = 1; $i <= $section_length; $i ++ ) {
			$section_id = $this->create_section( 'Section ' . $i, $course_id );

			if ( $section_id ) {

				$this->create_section_items( $section_id, $course_id );

			}
		}

	}

	/**
	 * Create section.
	 *
	 * @param string $name
	 * @param int    $course_id
	 *
	 * @return int
	 */
	protected function create_section( $name, $course_id ) {
		static $order = 0;

		global $wpdb;

		$data = array(
			'section_name'        => $name,
			'section_course_id'   => $course_id,
			'section_order'       => $order,
			'section_description' => $this->generate_title(),
		);

		$wpdb->insert(
			$wpdb->learnpress_sections,
			$data,
			array( '%s', '%d', '%d', '%s' )
		);

		if ( $wpdb->insert_id ) {
			$order ++;

			return $wpdb->insert_id;
		}

		return 0;
	}

	/**
	 * Create section items.
	 *
	 * @param int $section_id
	 * @param int $course_id
	 */
	protected function create_section_items( $section_id, $course_id ) {

		static $lesson_count = 1;
		static $quiz_count   = 1;

		$order = 0;

		$item_length = call_user_func_array( 'rand', self::$item_range );

		for ( $i = 1; $i < $item_length; $i ++ ) {
			$lesson_id = $this->create_lesson( 'Lesson ' . $lesson_count ++, $section_id, $course_id, $order );

			if ( $lesson_id ) {
				$order ++;

				if ( $i == 1 ) {
					update_post_meta( $lesson_id, '_lp_preview', 'yes' );
				}
			}
		}

		$quiz_id = $this->create_quiz( 'Quiz ' . $quiz_count ++, $section_id, $course_id, $order );

		if ( $quiz_id ) {
			$order ++;
		}
	}

	/**
	 * Create lesson.
	 *
	 * @param string $name
	 * @param int    $section_id
	 * @param int    $course_id
	 *
	 * @return int|WP_Error
	 */
	protected function create_lesson( $name, $section_id, $course_id, $order ) {
		global $wpdb;

		$data = array(
			'post_title'   => $name,
			'post_type'    => LP_LESSON_CPT,
			'post_status'  => 'publish',
			'post_content' => $this->generate_content(),
		);

		$lesson_id = wp_insert_post( $data );

		if ( $lesson_id ) {

			update_post_meta( $lesson_id, '_lp_sample_data', 'yes' );

			$section_data = array(
				'section_id' => $section_id,
				'item_id'    => $lesson_id,
				'item_type'  => LP_LESSON_CPT,
				'item_order' => absint( $order ),
			);

			$wpdb->insert(
				$wpdb->learnpress_section_items,
				$section_data,
				array( '%d', '%d', '%s', '%d' )
			);
		}

		return $lesson_id;
	}

	/**
	 * Create quiz.
	 *
	 * @param string $name
	 * @param int    $section_id
	 * @param int    $course_id
	 *
	 * @return int|WP_Error
	 */
	protected function create_quiz( $name, $section_id, $course_id, $order ) {
		global $wpdb;

		$data = array(
			'post_title'   => $name,
			'post_type'    => LP_QUIZ_CPT,
			'post_status'  => 'publish',
			'post_content' => $this->generate_content( 25, 40, 2 ),
		);

		$quiz_id = wp_insert_post( $data );

		if ( $quiz_id ) {

			$metas = array(
				'_lp_preview'              => 'no',
				// '_lp_minus_points'         => 0,
				// '_lp_show_hide_question'   => 'no',
				// '_lp_review_questions'     => 'yes',
				// '_lp_show_result'          => 'yes',
				'_lp_duration'             => ( rand( 1, 5 ) * 10 ) . ' ' . 'minute',
				'_lp_passing_grade'        => rand( 5, 9 ) * 10,
				// '_lp_retake_count'         => rand( 0, 10 ),
				// '_lp_archive_history'      => 'no',
				// '_lp_show_check_answer'    => '0',
				// '_lp_show_hint'            => '0',
				'_lp_sample_data'          => 'yes',
				'_lp_negative_marking'     => 'no',
				'_lp_minus_skip_questions' => 'no',
				'_lp_instant_check'        => 'no',
				'_lp_retake_count'         => '0',
				'_lp_pagination'           => '1',
				'_lp_review'               => 'yes',
				'_lp_show_correct_review'  => 'yes',
			);

			foreach ( $metas as $key => $value ) {
				update_post_meta( $quiz_id, $key, $value );
			}

			$section_data = array(
				'section_id' => $section_id,
				'item_id'    => $quiz_id,
				'item_type'  => LP_QUIZ_CPT,
				'item_order' => absint( $order ),
			);

			$wpdb->insert(
				$wpdb->learnpress_section_items,
				$section_data,
				array( '%d', '%d', '%s', '%d' )
			);

			$this->create_quiz_questions( $quiz_id );
		}

		return $quiz_id;
	}

	/**
	 * Create questions of a quiz.
	 *
	 * @param int $quiz_id
	 */
	protected function create_quiz_questions( $quiz_id ) {
		static $question_index = 1;
		global $wpdb;

		$question_count = call_user_func_array( 'rand', self::$question_range );
		for ( $i = 1; $i <= $question_count; $i ++ ) {
			$data = array(
				'post_title'   => 'Question ' . $question_index ++,
				'post_type'    => LP_QUESTION_CPT,
				'post_status'  => 'publish',
				'post_content' => $this->generate_content( 25, 40, 2 ),
			);

			$question_id = wp_insert_post( $data );

			if ( ! $question_id ) {
				continue;
			}

			$type = $this->get_question_type();

			update_post_meta( $question_id, '_lp_type', $type );
			update_post_meta( $question_id, '_lp_sample_data', 'yes' );

			$quiz_question_data = array(
				'quiz_id'     => $quiz_id,
				'question_id' => $question_id,
			);

			$wpdb->insert(
				$wpdb->learnpress_quiz_questions,
				$quiz_question_data,
				array( '%d', '%d' )
			);

			if ( $wpdb->insert_id ) {
				$this->create_question_answers( $question_id, $type );
			} else {
				error_log( 'create_quiz_questions => ', $wpdb->last_error );
			}
		}
	}

	/**
	 * Create answers for a question.
	 *
	 * @param int    $question_id
	 * @param string $type
	 */
	protected function create_question_answers( $question_id, $type ) {
		global $wpdb;

		$answers = $this->get_answers( $type );
		foreach ( $answers as $order => $answer ) {
			$data = array(
				'question_id' => $question_id,
				'title'       => $answer['title'],
				'value'       => $answer['value'],
				'is_true'     => $answer['is_true'],
				'order'       => $order + 1,
			);

			$wpdb->insert(
				$wpdb->learnpress_question_answers,
				$data,
				array( '%d', '%s', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Get random answers by type of question.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function get_answers( $type ) {
		$answers = array();

		$option_count = $type === 'true_or_false' ? 2 : call_user_func_array( 'rand', self::$answer_range );

		for ( $i = 1; $i <= $option_count; $i ++ ) {
			$answers[] = array(
				'title'   => $this->generate_title(),
				'value'   => learn_press_random_value(),
				'is_true' => 'no',
			);
		}

		// Set option is TRUE randomize
		if ( $type !== 'multi_choice' ) {
			$at                        = rand( 0, sizeof( $answers ) - 1 );
			$answers[ $at ]['is_true'] = 'yes';
			$answers[ $at ]['title']   = _x( '[TRUE] - ', 'install-sample-course', 'learnpress' ) . $answers[ $at ]['title'];
		} else {
			$has_true_option = false;
			while ( ! $has_true_option ) {
				foreach ( $answers as $k => $v ) {
					$answers[ $k ]['is_true'] = rand( 0, 100 ) % 2 ? 'yes' : 'no';

					if ( $answers[ $k ]['is_true'] === 'yes' ) {
						$answers[ $k ]['title'] = _x( ' [TRUE] - ', 'install-sample-course', 'learnpress' ) . $answers[ $k ]['title'];
						$has_true_option        = true;
					}
				}
			}
		}

		return $answers;
	}

	/**
	 * Get random type for a question.
	 *
	 * @return string
	 */
	protected function get_question_type() {
		$types = array(
			'true_or_false',
			'single_choice',
			'multi_choice',
		);

		return $types[ rand( 0, sizeof( $types ) - 1 ) ];
	}
}

new LP_Install_Sample_Data();
