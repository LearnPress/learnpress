<?php
/**
 * Place the function will be deprecated or may not used here
 */

throw new Exception( "This file will not included in anywhere" );
function learn_press_settings_payment() {
	?>
	<h3><?php _e( 'Payment', 'learnpress' ); ?></h3>
	<?php
}

/**
 * Remove all filter
 *
 * @param  String  $tag
 * @param  boolean $priority
 *
 * @return boolean
 */
function learn_press_remove_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	if ( !function_exists( 'bbpress' ) ) return;
	$bbp = bbpress();

	// Filters exist
	if ( isset( $wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$bbp->filters->wp_filter[$tag][$priority] = $wp_filter[$tag][$priority];

			// Unset the filters
			unset( $wp_filter[$tag][$priority] );

			// Priority is empty
		} else {

			// Store filters in a backup
			$bbp->filters->wp_filter[$tag] = $wp_filter[$tag];

			// Unset the filters
			unset( $wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $merged_filters[$tag] ) ) {

		// Store filters in a backup
		$bbp->filters->merged_filters[$tag] = $merged_filters[$tag];

		// Unset the filters
		unset( $merged_filters[$tag] );
	}

	return true;
}

/*
 * Rewrite url
 */

add_action( 'init', 'learn_press_add_rewrite_tag' );
function learn_press_add_rewrite_tag() {
	add_rewrite_tag( '%user%', '([^/]*)' );
	flush_rewrite_rules();
}


add_filter( 'page_rewrite_rules', 'learn_press_add_rewrite_rule' );
function learn_press_add_rewrite_rule( $rewrite_rules ) {
	// The most generic page rewrite rule is at end of the array
	// We place our rule one before that
	end( $rewrite_rules );
	$last_pattern     = key( $rewrite_rules );
	$last_replacement = array_pop( $rewrite_rules );
	$page_id          = learn_press_get_profile_page_id();
	$rewrite_rules += array(
		'^profile/([^/]*)' => 'index.php?page_id=' . $page_id . '&user=$matches[1]',
		$last_pattern      => $last_replacement
	);

	return $rewrite_rules;
}

/*
 * Editing permalink notification when using LearnPress profile
 */
add_action( 'admin_notices', 'learn_press_edit_permalink' );
add_action( 'network_admin_notices', 'learn_press_edit_permalink' );
function learn_press_edit_permalink() {

	// Setting up notification
	$check = get_option( '_lpr_ignore_setting_up' );
	if ( !$check && current_user_can( 'manage_options' ) ) {
		echo '<div id="lpr-setting-up" class="updated"><p>';
		echo sprintf(
			__( '<strong>LearnPress is almost ready</strong>. <a class="lpr-set-up" href="%s">Setting up</a> something right now is a good idea. That\'s better than you <a class="lpr-ignore lpr-set-up">ignore</a> the message.', 'learnpress' ),
			esc_url( add_query_arg( array( 'page' => 'learn_press_settings' ), admin_url( 'options-general.php' ) ) )
		);
		echo '</p></div>';
	}

	// Add notice if no rewrite rules are enabled
	global $wp_rewrite;
	if ( learn_press_has_profile_method() ) {
		if ( empty( $wp_rewrite->permalink_structure ) ) {
			echo '<div class="fade error"><p>';
			echo sprintf(
				wp_kses(
					__( '<strong>LearnPress Profile is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'learnpress' ),
					array(
						'a'      => array(
							'href' => array()
						),
						'strong' => array()
					)
				),
				admin_url( 'options-permalink.php' )
			);
			echo '</p></div>';
		}
	}
}

function learn_press_submit_answer() {

	_deprecated_function( 'learn_press_submit_answer', '0.9.15', false );

	$quiz_id         = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	$question_id     = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$next_id         = !empty( $_REQUEST['next_id'] ) ? intval( $_REQUEST['next_id'] ) : learn_press_get_next_question( $quiz_id, $question_id );
	$question_answer = isset( $_REQUEST['question_answer'] ) ? $_REQUEST['question_answer'] : null;
	$finish          = isset( $_REQUEST['finish'] ) ? $_REQUEST['finish'] : null;

	$user_id = get_current_user_id();
	$json    = array();

	$ques = lpr_get_question( $question_id );
	if ( $ques ) {
		$ques->submit_answer( $quiz_id, $question_answer );
	}
	ob_start();
	if ( $next_id ) {
		do_action( 'learn_press_submit_answer', $question_answer, $question_id, $quiz_id, $user_id, false );
		learn_press_get_template( 'quiz/form-question.php', array( 'question_id' => $next_id, 'course_id' => learn_press_get_course_by_quiz( $quiz_id ) ) );
	} else {
		$question_ids             = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
		$quiz_completed           = get_user_meta( $user_id, '_lpr_quiz_completed', true );
		$quiz_completed[$quiz_id] = current_time( 'timestamp' );
		update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( !learn_press_user_has_finished_course( $course_id ) ) {
			if ( learn_press_user_has_completed_all_parts( $course_id, $user_id ) ) {
				learn_press_finish_course( $course_id, $user_id );
			}
		}
		learn_press_get_template( 'quiz/result.php' );
		$json['quiz_completed'] = true;
		do_action( 'learn_press_submit_answer', $question_answer, $question_id, $quiz_id, $user_id, true );
	}
	$output = ob_get_clean();
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$json['html']     = $output;
		$json['redirect'] = apply_filters( 'learn_press_submit_answer_redirect_url', get_the_permalink( $quiz_id ), $question_answer, $question_id, $quiz_id, $user_id );
		learn_press_send_json( $json );
	}
}

add_action( 'wp_ajax_learn_press_submit_answer', 'learn_press_submit_answer' );
add_action( 'wp_ajax_nopriv_learn_press_submit_answer', 'learn_press_submit_answer' );

add_action( 'learn_press_frontend_action_submit_answer', 'learn_press_submit_answer' );


function learn_press_load_question() {
	$question_id = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$quiz_id     = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	/*$ques = lpr_get_question( $question_id );
	if( $ques ){
		$quiz_answers = learn_press_get_question_answers(null, $quiz_id );
		$ques->render( array(
			'answer' => isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : null
		));
	}*/
	learn_press_get_template( 'quiz/form-question.php', array( 'question_id' => $question_id, 'course_id' => learn_press_get_course_by_quiz( $quiz_id ) ) );

	die();
}

add_action( 'wp_ajax_learn_press_load_question', 'learn_press_load_question' );
add_action( 'wp_ajax_nopriv_learn_press_load_question', 'learn_press_load_question' );



function learn_press_show_answer() {
	$quiz_id         = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	$question_id     = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$question_answer = isset( $_REQUEST['question_answer'] ) ? $_REQUEST['question_answer'] : null;

	$user_id = get_current_user_id();
	$json    = array();
	ob_start();
	$ques = lpr_get_question( $question_id );
	if ( $ques ) {
		$ques->submit_answer( $quiz_id, $question_answer );
	}
	global $quiz;
	$user_id      = get_current_user_id();
	$quiz_answers = learn_press_get_question_answers( null, $quiz_id );
	$answer       = isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : array();
	switch ( $ques->get_type() ) {
		case 'multi_choice':
			?>
			<ul class="lpr-question-hint">
				<?php if ( $answers = $ques->get( 'options.answer' ) ) foreach ( $answers as $k => $ans ):
					$classes = array();
					if ( in_array( $k, $answer ) ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="checkbox"
								   disabled="disabled" <?php checked( in_array( $k, $answer ) ? 1 : 0 ); ?> />
							<?php echo $ques->get( "options.answer.{$k}.text" ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			break;
		case 'single_choice':
			?>
			<ul class="lpr-question-hint">
				<?php if ( $answers = $ques->get( 'options.answer' ) ) foreach ( $answers as $k => $ans ):
					$classes = array();
					if ( $k == $answer ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="radio" disabled="disabled" <?php checked( $k == $answer ? 1 : 0 ); ?> />
							<?php echo $ques->get( "options.answer.{$k}.text" ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			break;
		case 'true_or_false':
			?>
			<ul class="lpr-question-hint">
				<?php
				for ( $k = 0; $k < 2; $k ++ ) {
					$classes = array();
					if ( $k == $answer ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="radio" disabled="disabled" <?php checked( $answer == $k ? 1 : 0 ); ?> />
							<?php echo $ques->get( 'options.answer.' . $k . '.text' ); ?>
						</label>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
			break;
		default:
			do_action( 'learn_press_question_suggestion_' . $ques->get_type(), $ques, $answer );
	}
	?>
	<h4><?php _e( "Answer explanation", 'learnpress' ) ?></h4>
	<p><?php echo $ques->get( 'options.explaination' ) ?></p>
	<?php
	$json['html'] = ob_get_clean();

	wp_send_json( $json );

	die();
}

add_action( 'wp_ajax_learn_press_show_answer', 'learn_press_show_answer' );
add_action( 'wp_ajax_nopriv_learn_press_show_answer', 'learn_press_show_answer' );

function lpr_get_question_types() {
	_deprecated_function( __FUNCTION__, '1.0', 'learn_press_question_types' );
	return learn_press_question_types();
}
