<?php
/**
 * Handle renamed/removed hooks
 *
 */
global $lp_map_deprecated_filters;

$lp_map_deprecated_filters = array(
	'learn_press_register_add_ons' => 'learn_press_loaded'
);

foreach ( $lp_map_deprecated_filters as $old => $new ) {
	add_filter( $old, 'lp_deprecated_filter_mapping', 9999999 );
}

function lp_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $lp_map_deprecated_filters, $wp_filter;
	$filter = current_filter();
	if ( ! empty( $wp_filter[ $filter ] ) && count( $wp_filter[ $filter ] ) > 1 ) {
		_deprecated_function( 'The ' . $filter . ' hook', '1.0', $lp_map_deprecated_filters[ $filter ] );
	}

	return $data;
}

function learn_press_text_image( $text = null, $args = array() ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$width      = 200;
	$height     = 150;
	$font_size  = 1;
	$background = 'FFFFFF';
	$color      = '000000';
	$padding    = 20;
	extract( $args );

	// Output to browser
	if ( empty( $_REQUEST['debug'] ) ) {
		header( 'Content-Type: image/png' );
	}
	/*
    $uniqid = md5( serialize( array( 'width' => $width, 'height' => $height, 'text' => $text, 'background' => $background, 'color' => $color ) ) );
    @mkdir( LP_PLUGIN_PATH . '/cache' );
    $cache = LP_PLUGIN_PATH . '/cache/' . $uniqid . '.cache';
    if( file_exists( $cache ) ){
        readfile( $cache );
        die();
    }*/

	$im = imagecreatetruecolor( $width, $height );

	list( $r, $g, $b ) = sscanf( "#{$background}", "#%02x%02x%02x" );
	$background = imagecolorallocate( $im, $r, $g, $b );

	list( $r, $g, $b ) = sscanf( "#{$color}", "#%02x%02x%02x" );
	$color = imagecolorallocate( $im, $r, $g, $b );

	// Set the background to be white
	imagefilledrectangle( $im, 0, 0, $width, $height, $background );

	// Path to our font file
	$font = LP_PLUGIN_PATH . '/assets/fonts/Sumana-Regular.ttf';
	$x    = $width;
	$loop = 0;
	do {
		// First we create our bounding box for the first text
		$bbox = imagettfbbox( $font_size, 0, $font, $text );
		// This is our cordinates for X and Y
		$x = $bbox[0] + ( imagesx( $im ) / 2 ) - ( $bbox[4] / 2 );
		$y = $bbox[1] + ( imagesy( $im ) / 2 ) - ( $bbox[5] / 2 );
		$font_size ++;
		if ( $loop ++ > 100 ) {
			break;
		}
	} while ( $x > $padding );
	// Write it
	imagettftext( $im, $font_size, 0, $x - 5, $y, $color, $font, $text );
	imagepng( $im );
	//readfile( $cache );
	imagedestroy( $im );
}

/**
 * Get all lessons in a course
 *
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_lessons( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$lessons    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP_LESSON_CPT ) {
						$lessons[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $lessons;
}

/**
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_course_quizzes( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$quizzes    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP_QUIZ_CPT ) {
						$quizzes[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $quizzes;
}

// Deprecated template functions


if ( ! function_exists( 'learn_press_course_content_lesson' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_course_content_lesson() {
		learn_press_get_template( 'content-lesson/summary.php' );
	}
}

if ( ! function_exists( 'learn_press_course_lesson_data' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_data() {
		$course = LP()->course;
		if ( ! $course ) {
			return;
		}
		if ( ! ( $lesson = $course->current_lesson ) ) {
			return;
		}
		?>
        <input type="hidden" name="learn-press-lesson-viewing" value="<?php echo $lesson->id; ?>"/>
		<?php
	}
}

if ( ! function_exists( 'learn_press_course_lesson_description' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_description() {
		learn_press_get_template( 'content-lesson/description.php' );
	}
}

if ( ! function_exists( 'learn_press_course_quiz_description' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_course_quiz_description() {
		learn_press_get_template( 'single-course/content-quiz.php' );
	}
}


if ( ! function_exists( 'learn_press_course_lesson_complete_button' ) ) {
	/**
	 * Display lesson complete button
	 */
	function learn_press_course_lesson_complete_button() {
		learn_press_get_template( 'content-lesson/complete-button.php' );
	}
}


if ( ! function_exists( 'learn_press_course_lesson_navigation' ) ) {
	/**
	 * Display lesson navigation
	 */
	function learn_press_course_lesson_navigation() {
		learn_press_get_template( 'content-lesson/navigation.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_preview_mode' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_preview_mode() {
		learn_press_get_template( 'content-quiz/preview-mode.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_left_start_wrap' ) ) {
	function learn_press_single_quiz_left_start_wrap() {
		learn_press_get_template( 'content-quiz/left-start-wrap.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_question' ) ) {
	/**
	 * Output the single question for quiz
	 */
	function learn_press_single_quiz_question() {
		learn_press_get_template( 'content-quiz/content-question.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_result' ) ) {
	/**
	 * Output the result for the quiz
	 */
	function learn_press_single_quiz_result() {
		learn_press_get_template( 'content-quiz/result.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_questions_nav' ) ) {
	/**
	 * Output the navigation to next and previous questions
	 */
	function learn_press_single_quiz_questions_nav() {
		learn_press_get_template( 'content-quiz/nav.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_questions' ) ) {
	/**
	 * Output the list of questions for quiz
	 */
	function learn_press_single_quiz_questions() {
		learn_press_get_template( 'content-quiz/questions.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_history' ) ) {
	/**
	 * Output the history of a quiz
	 */
	function learn_press_single_quiz_history() {
		learn_press_get_template( 'content-quiz/history.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_left_end_wrap' ) ) {
	function learn_press_single_quiz_left_end_wrap() {
		learn_press_get_template( 'content-quiz/left-end-wrap.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_sidebar' ) ) {
	/**
	 * Output the sidebar for a quiz
	 */
	function learn_press_single_quiz_sidebar() {
		learn_press_get_template( 'content-quiz/sidebar.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'content-quiz/intro.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_timer' ) ) {
	/**
	 * Output the quiz countdown timer
	 */
	function learn_press_single_quiz_timer() {
		learn_press_get_template( 'content-quiz/timer.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_buttons' ) ) {
	/**
	 * Output the buttons for quiz actions
	 */
	function learn_press_single_quiz_buttons() {
		learn_press_get_template( 'content-quiz/buttons.php' );
	}
}

if ( ! function_exists( 'learn_press_single_quiz_description' ) ) {
	/**
	 * Output the content of the quiz
	 */
	function learn_press_single_quiz_description() {
		learn_press_get_template( 'content-quiz/description.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'content-quiz/intro.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_sidebar_buttons' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_sidebar_buttons() {
		learn_press_get_template( 'content-quiz/sidebar-buttons.php' );
	}
}

if ( ! function_exists( '_learn_press_default_course_tabs' ) ) {

	/**
	 * Add default tabs to course
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	function _learn_press_default_course_tabs( $tabs = array() ) {
		_deprecated_function( __FUNCTION__, '3.0.0', 'learn_press_get_course_tabs' );

		return learn_press_get_course_tabs();
	}
}


function learn_press_sanitize_json( $string ) {

	echo json_encode( $string );

	return $string;
}


function learn_press_get_subtabs_course() {
	$subtabs = array(
		'all'       => __( 'All', 'learnpress' ),
		'learning'  => __( 'Learning', 'learnpress' ),
		'purchased' => __( 'Purchased', 'learnpress' ),
		'finished'  => __( 'Finished', 'learnpress' ),
		'own'       => __( 'Owned', 'learnpress' )
	);

	$subtabs = apply_filters( 'learn_press_profile_tab_courses_subtabs', $subtabs );

	return $subtabs;
}


# -------------------------------
# Validation Data Settings Page Before Save
add_filter( 'learn_press_update_option_value', 'learn_press_validation_data_before_save', 10, 2 );

function learn_press_validation_data_before_save( $value = '', $name = '' ) {
	if ( $name === 'learn_press_profile_endpoints' ) {

		if ( empty( $value['profile-courses'] ) ) {
			$value['profile-courses'] = 'courses';
		}
	}

	return $value;
}


// Show filters for students list
function learn_press_get_students_list_filter() {
	$filter = array(
		'all'         => esc_html__( 'All', 'learnpress' ),
		'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
		'finished'    => esc_html__( 'Finished', 'learnpress' )
	);

	return apply_filters( 'learn_press_get_students_list_filter', $filter );
}


function learn_press_get_request_args( $args = array() ) {
	$request = array();
	if ( $args ) {
		foreach ( $args as $key ) {
			$request[] = array_key_exists( $key, $_REQUEST ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : false;
		}
	}

	return $request;
}


/**
 * Redirect to question if user access to a quiz that user has started
 *
 * @param string
 *
 * @return string
 */
function learn_press_redirect_to_question( $template ) {
	global $post_type;
	if ( is_single() && $post_type == LP_QUIZ_CPT ) {
		$user        = learn_press_get_current_user();
		$quiz_id     = get_the_ID();
		$quiz_status = $user->get_quiz_status( $quiz_id );
		if ( $quiz_status == 'started' && learn_press_get_quiz_time_remaining( $user->get_id(), $quiz_id ) == 0 && get_post_meta( $quiz_id, '_lpr_duration', true ) ) {
			$user->finish_quiz( $quiz_id );
			$quiz_status = 'completed';
		}
		$redirect = null;
		if ( learn_press_get_request( 'question' ) && $quiz_status == '' ) {
			$redirect = get_the_permalink( $quiz_id );
		} elseif ( $quiz_status == 'started' ) {
			if ( learn_press_get_request( 'question' ) ) {
			} else {
				$redirect = learn_press_get_user_question_url( $quiz_id );
			}
		} elseif ( $quiz_status == 'completed' && learn_press_get_request( 'question' ) ) {
			$redirect = get_the_permalink( $quiz_id );
		}
		if ( $redirect && ! learn_press_is_current_url( $redirect ) ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	return $template;
}


function learn_press_output_question_nonce( $question ) {
	printf( '<input type="hidden" name="update-question-nonce" value="%s" />', wp_create_nonce( 'current-question-nonce-' . $question->id ) );
}

add_action( 'learn_press_after_question_wrap', 'learn_press_output_question_nonce' );


if ( ! function_exists( 'learn_press_course_nav_items' ) ) {
	/**
	 * Displaying course items navigation
	 *
	 * @param null $item_id
	 * @param null $course_id
	 */
	function learn_press_course_nav_items( $item_id = null, $course_id = null ) {
		learn_press_get_template( 'single-course/nav-items.php', array(
			'course_id'    => $course_id,
			'item_id'      => $item_id,
			'content_only' => learn_press_is_content_item_only()
		) );
	}
}

/**
 * Load course item content only
 */
function learn_press_load_content_item_only( $name ) {
	if ( learn_press_is_content_item_only() ) {
		if ( LP()->global['course-item'] ) {
			remove_action( 'get_header', 'learn_press_load_content_item_only' );
			learn_press_get_template( 'single-course/content-item-only.php' );
			die();
		}
	}
}