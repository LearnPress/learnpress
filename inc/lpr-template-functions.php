<?php
/**
 * @file
 * 
 * LearnPress Template Functions
 *
 * Common functions for template
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !function_exists( 'learn_press_is_enrolled_course' ) ) {
    /**
     * Verify course access
     *
     * @param int $course_id
     * @param int $user_id
     * @return boolean
     */
    function learn_press_is_enrolled_course( $course_id = null, $user_id = null ){
        if( ! $course_id ) {
            $course_id = get_the_ID();
        }
        if( ! $user_id ) {
            $user_id = get_current_user_id();
        }



        $course_taken = get_user_meta($user_id, '_lpr_user_course', true);
        if ($course_taken) {
            if (in_array($course_id, $course_taken)) {
                return true;
            }
        }
        return false;
    }
}

if( ! function_exists( 'learn_press_course_price' ) ) {
    /**
     * Display course price      
     */
    function learn_press_course_price(){
        learn_press_get_template('course/price.php');
    }
}

if( ! function_exists( 'learn_press_course_categories' ) ) {
    /**
     * Display course categories     
     */
    function learn_press_course_categories(){
        learn_press_get_template('course/categories.php');
    }
}

if( ! function_exists( 'learn_press_course_tags' ) ) {
    /**
     * Display course tags     
     */
    function learn_press_course_tags(){
        learn_press_get_template('course/tags.php');
    }
}

if( ! function_exists( 'learn_press_course_students' ) ) {
    /**
     * Display course students
     */
    function learn_press_course_students(){
        learn_press_get_template('course/students.php');
    }
}

if( ! function_exists( 'learn_press_course_instructor' ) ) {
    /**
     * Display course instructor     
     */
    function learn_press_course_instructor(){
        learn_press_get_template('course/instructor.php');
    }
}

if( ! function_exists( 'learn_press_course_curriculum' ) ) {
    /**
     * Display course curriculum     
     */
    function learn_press_course_curriculum(){
        $curriculum = learn_press_get_course_curriculum();
        if ($tpl = learn_press_locate_template('course/curriculum.php')) {
            require $tpl;
        }
    }
}

if( ! function_exists( 'learn_press_course_content' ) ) {
    /**
     * Display course content     
     */
    function learn_press_course_content(){
        learn_press_get_template('course/content.php');
    }
}

if( ! function_exists( 'learn_press_course_payment_form' ) ) {
    /**
     * Course payment form      
     */
    function learn_press_course_payment_form(){

        learn_press_get_template('course/payment-form.php');

    }
}

if( ! function_exists( 'learn_press_course_enroll_button' ) ) {
    /**
     * Display course enroll button     
     */
    function learn_press_course_enroll_button(){
        $course_status = learn_press_get_user_course_status();
        // only show enroll button if user had not enrolled
        if ( '' == $course_status && learn_press_course_enroll_required() ) {
            learn_press_get_template('course/enroll-button.php');
        }
    }
}

if( ! function_exists( 'learn_press_course_status_message' ) ) {
    /**
     * Display course status message     
     */
    function learn_press_course_status_message(){
        $course_status = learn_press_get_user_course_status();

        // only show enroll button if user had not enrolled
        if ($course_status && ('completed' != strtolower($course_status))) {
            learn_press_get_template('course/course-pending.php');
        }
    }
}

if( ! function_exists( 'learn_press_course_thumbnail' ) ) {
    /**
     * Display Course Thumbnail     
     */
    function learn_press_course_thumbnail(){
        learn_press_get_template('course/thumbnail.php');
    }
}

/**
 * Get curriculum of a course
 *
 * @param int $course_id
 * @return mixed
 */
function learn_press_get_course_curriculum( $course_id = null ) {
    if ( !$course_id ) {
        global $course;
        if( $course ) $course_id = $course->ID;
    }
    $course_curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
    return apply_filters( 'learn_press_course_curriculum', $course_curriculum, $course_id );
}

if( !function_exists( 'learn_press_wrapper_start' ) ) {
    /**
     * Wrapper Start     
     */
    function learn_press_wrapper_start(){
        learn_press_get_template('global/before-main-content.php');
    }
}

if( ! function_exists( 'learn_press_wrapper_end' ) ) {
    /**
     * wrapper end     
     */
    function learn_press_wrapper_end(){
        learn_press_get_template('global/after-main-content.php');
    }
}
/* This block created by Tu Nguyen */

if( !function_exists( 'learn_press_print_quiz_question_content_script' ) ){
    /**
     * Output js script configuration for single quiz page
     */
    function learn_press_print_quiz_question_content_script(){
        $current_question_id = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
        $questions = learn_press_get_quiz_questions();
        if( $questions ) {
            $question_ids = array_keys($questions);
        }else{
            $question_ids = array();
        }
        if( !$current_question_id || !in_array( $current_question_id, $question_ids ) ){
            $current_question_id = reset( $question_ids );
        }
        $question = LPR_Question_Type::instance( $current_question_id );
        $user_id = get_current_user_id();
        global $quiz;

        $js = array(
            'quiz_id'           => get_the_ID(),
            'question_id'       => $current_question_id,
            'questions'         => $question_ids,
            'time_remaining'    => learn_press_get_quiz_time_remaining( $user_id, $quiz->ID),
            'quiz_started'      => learn_press_user_has_started_quiz(),
            'quiz_completed'    => learn_press_user_has_completed_quiz()
        );
        ?>
        <script type="text/javascript">
            var dataFromParent;
            function init() {
                console.log(dataFromParent);
                jQuery('.quiz-main').attr('course-id', dataFromParent);
            }
            jQuery(function() {
                LearnPress.singleQuizInit( <?php echo json_encode( $js );?> );
            });
        </script>
    <?php
    }
}

if( !function_exists( 'learn_press_single_quiz_title' ) ){
    /**
     * Output the title of the quiz
     */
    function learn_press_single_quiz_title(){
        if( learn_press_user_can_view_quiz() ) {
            learn_press_get_template('quiz/title.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_description' ) ) {
    /**
     * Output the content of the quiz
     */
    function learn_press_single_quiz_description(){
        if( learn_press_user_can_view_quiz() ) {
            echo '<div class="quiz-content">';
            the_content();
            echo '</div>';
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_result' ) ) {
    /**
     * Output the result of a quiz
     */
    function learn_press_single_quiz_result(){
        if (learn_press_user_has_completed_quiz()) {
            learn_press_get_template('quiz/result.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_percentage' ) ) {
    /**
     * Output the percentage of a quiz result
     */
    function learn_press_single_quiz_percentage(){
	    $course_id = learn_press_get_course_by_quiz( get_the_ID() );
	    $final_quiz = lpr_get_final_quiz( $course_id );
        if (learn_press_user_has_completed_quiz() && ( $final_quiz == get_the_ID() ) ) {
            learn_press_get_template('quiz/percentage.php');
        }
    }
}

if( !function_exists( 'learn_press_course_percentage' ) ) {
    /**
     * Output the percentage of a quiz result
     */
    function learn_press_course_percentage(){
        if (learn_press_user_has_completed_quiz( null, 1183 )) {
            learn_press_get_template('course/course-result.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_load_question' ) ) {
    /**
     * Output the content of current question
     */
    function learn_press_single_quiz_load_question(){
        if (!learn_press_user_has_completed_quiz() && learn_press_user_can_view_quiz() ) {
            learn_press_get_template('quiz/questions.php');
        }
    }
}


if( !function_exists( 'learn_press_quiz_question_nav_buttons' ) ) {
    /**
     * Output navigator button for next/prev question
     */
    function learn_press_quiz_question_nav_buttons(){
        learn_press_get_template('quiz/nav.php');
    }
}

if( !function_exists( 'learn_press_quiz_question_nav' ) ) {
    /**
     * Output the content of current question
     */
    function learn_press_quiz_question_nav(){
        $current_question = learn_press_get_question_position();
        $question_id = $current_question['id'];
        //echo rand() . "[$question_id]";
        learn_press_get_template( 'quiz/form-question.php', array( 'question_id' => $question_id, 'course_id' => learn_press_get_course_by_quiz( get_the_ID() ) ) );
    }
}

if( ! function_exists( 'learn_press_output_question' ) ){
    function learn_press_output_question( $question_id, $with_answered = true ){
        if ( $question_id ) {
            $question = LPR_Question_Type::instance( $question_id );
            $answered = null;
            if( $with_answered ) {
                $answers = learn_press_get_question_answers( null, learn_press_get_quiz_id( 0 ) );
                if( isset( $answers[$question_id] ) ) {
                    $answered = array('answer' => $answers[$question_id]);
                }
            }
            $question && $question->render( $answered  );
        }
    }
}

if( !function_exists( 'learn_press_before_main_quiz_content' ) ) {
    /**
     * Output wrapper element
     */
    function learn_press_before_main_quiz_content(){
        learn_press_get_template('global/before-main-content.php');
    }
}

if( !function_exists( 'learn_press_after_main_quiz_content' ) ) {
    /**
     * Hook to after main quiz content
     */
    function learn_press_after_main_quiz_content(){
        learn_press_get_template('global/after-main-content.php');
    }
}

if( !function_exists( 'learn_press_single_quiz_content_page' ) ){
    /**
     * Output main content of a quiz
     */
    function learn_press_single_quiz_content_page(){
        learn_press_get_template( 'content-quiz.php' );
    }
}

if( !function_exists( 'learn_press_check_question_answer' ) ){
    /**
     * Output main content of a quiz
     */
    function learn_press_check_question_answer(){
        $check_answer = get_post_meta( get_the_ID(), '_lpr_show_question_answer', true );        
        if( $check_answer ) {
            learn_press_get_template( 'quiz/button-check-answer.php' );
        }        
    }
}

if( !function_exists( 'learn_press_single_quiz_sidebar' ) ){
    /**
     * Output quiz sidebar
     */
    function learn_press_single_quiz_sidebar(){
        if( learn_press_user_can_view_quiz() ) {
            learn_press_get_template('quiz/sidebar.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_no_question' ) ){
    /**
     * View no question
     */
    function learn_press_single_quiz_no_question(){
        if( learn_press_user_can_view_quiz() ) {
            learn_press_get_template('quiz/no-question.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_no_preview_message' ) ){
    /**
     * Warning no-preview message
     */
    function learn_press_single_quiz_no_preview_message(){
        learn_press_get_template( 'quiz/no-preview-message.php' );
    }
}

if( !function_exists( 'learn_press_display_course_link' ) ){
    /**
     * Display course link
     */
    function learn_press_display_course_link(){
        global $quiz;
        echo '<div class="clearfix"></div><a class="back-to-course" href="' . get_permalink( get_post_meta( $quiz->ID, '_lpr_course', true ) ) . '"><i class="fa fa-angle-double-left"></i>' . __( 'Back to Course', 'learn_press' ) . '</a>';
    }
}

if( !function_exists( 'learn_press_permission_to_view_page' ) ){
    /**
     * Check permission to view page
     * @param  file $template
     * @return file
     */
    function learn_press_permission_to_view_page( $template/*, $slug, $name*/ ){
        if( get_post_type() == 'lpr_quiz' && is_single() ){
            if( ! learn_press_user_can_view_quiz() ) {
                // learn_press_404_page();
                $quiz_id = get_the_ID();
                $course_id = get_post_meta( $quiz_id, '_lpr_course', true );                
                wp_redirect( get_permalink( $course_id ) );
                exit();                
            }
        }
        /*if( $slug == 'single' && 'quiz' == $name ){
            echo "[",learn_press_user_can_view_quiz(),"]";
            if( ! learn_press_user_can_view_quiz() ) {
                learn_press_404_page();
                exit();
            }
        }*/
        return $template;
    }
}

if( !function_exists( 'learn_press_order_details_table' ) ){
    /**
     * Order Detail
     * @param  String $order
     * @return mixed
     */
    function learn_press_order_details_table( $order ){
        learn_press_get_template( 'order/order-details.php', array( 'order' => learn_press_get_order( $order ) ) );
    }
}

if( !function_exists( 'learn_press_frontend_single_quiz_scripts' ) ) {
    /**
     * Enqueue script for single-quiz page
     */
    function learn_press_frontend_single_quiz_scripts(){
        if (is_single() && 'lpr_quiz' == get_post_type()) {
            wp_dequeue_script('lpr-learnpress-js');
            wp_enqueue_script('tojson', LPR_PLUGIN_URL . '/assets/js/toJSON.js', array('jquery'));
            wp_enqueue_script('jquery-cookie', LPR_PLUGIN_URL . '/assets/js/jquery.cookie.js', array('jquery'));
            wp_enqueue_script('single-quiz', LPR_PLUGIN_URL . '/assets/js/single-quiz.js', array('jquery-cookie'));
        }
    }
}

if( !function_exists( 'learn_press_get_id_from_post' ) ) {
    /**
     * Get the ID of current post if the ID passed is null
     *
     * @author  TuNN
     * @param   int $id The ID of a post
     * @return  int
     */
    function learn_press_get_id_from_post($id = null){
        if (!$id) {
            global $post;
            if ($post) $id = $post->ID;
        }
        return $id;
    }
}

if( !function_exists('learn_press_single_quiz_list_questions') ) {
    /**
     * Display all questions in a quiz
     *
     * @author  TuNN
     *
     * @param   int $quiz_id
     * @return  void
     */
    function learn_press_single_quiz_list_questions( $quiz_id = null ){
        learn_press_get_template_part('single', 'quiz');
    }
}

if( !function_exists('learn_press_single_quiz_buttons') ) {
    /**
     * Output the button of a quiz
     */
    function learn_press_single_quiz_buttons(){
        learn_press_get_template('quiz/buttons.php');
    }
}

if( !function_exists( 'learn_press_single_quiz_time_counter' ) ) {
    /**
     * Output the countdown timer of the quiz
     */
    function learn_press_single_quiz_time_counter(){
        if( ! learn_press_user_has_completed_quiz() ) {
            learn_press_get_template('quiz/time-counter.php');
        }
    }
}

if( !function_exists( 'learn_press_single_quiz_questions' ) ) {
    /**
     * Output the list of questions in sidebar
     */
    function learn_press_single_quiz_questions(){
        learn_press_get_template('quiz/sidebar-questions.php');
    }
}

if( ! function_exists( 'learn_press_404_page' ) ){
    /**
     * Display 404 page
     */
    function learn_press_404_page(){
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 );
        exit();
    }
}

if( ! function_exists( 'learn_press_finish_course_button' ) ){
    /**
     * Displays the button to allows the user can finish the course by manual
     * depending on the Passing Condition setting in the course
     *
     * @author TuNguyen
     */
    function learn_press_finish_course_button(){
        if( learn_press_user_has_passed_conditional() && ! learn_press_user_has_finished_course() ){
            learn_press_get_template("course/finish-course-button.php");
        }
    }
}

if( ! function_exists( 'learn_press_course_finished_message' ) ){
    /**
     * Display the message about status of the course after user finished
     */
    function learn_press_course_finished_message(){
        if( learn_press_user_has_finished_course() ){
            $quiz_id = lpr_get_final_quiz( get_the_ID() );
            learn_press_get_template('course/course-result.php', array( 'quiz_id' => $quiz_id ) );
        }
    }
}

if( ! function_exists( 'learn_press_course_remaining_time' ) ){
    /**
     * Show the time remain of a course
     */
    function learn_press_course_remaining_time(){
        if( ! learn_press_user_has_finished_course() && $text = learn_press_get_course_remaining_time() ){
            learn_press_message( sprintf( __( 'This course will end within %s next' ), $text ) );
        }
    }
}

if( ! function_exists( 'learn_press_passed_conditional' ) ){
    /**
     * Show the message let user know that they can finish the course if they want
     */
    function learn_press_passed_conditional(){
        if( learn_press_user_has_passed_conditional() && ! learn_press_user_has_finished_course() ){
            $passing_condition = learn_press_get_course_passing_condition();
            learn_press_message( sprintf( __( 'You have passed %d percent of course and now you can finish this course if you want', 'learn_press' ), $passing_condition  ) );
        }
    }
}

if( ! function_exists( 'learn_press_course_retake_button' ) ){
    /**
     * Display the button let user can retake a course
     */
    function learn_press_course_retake_button(){
        if( learn_press_user_has_finished_course() && learn_press_user_can_retake_course() ){
            learn_press_get_template( 'course/button-retake.php' );
        }
    }
}

if( ! function_exists( 'learn_press_quiz_hint' ) ){
    /**
     * Display the correct/wrong answers of a quiz in result page
     * @param $question_id
     */
    function learn_press_quiz_hint( $question_id ){
        global $quiz;
        $user_id = get_current_user_id();

        if( ! learn_press_user_has_completed_quiz( $user_id, $quiz->ID ) || ! get_post_meta( $quiz->ID, '_lpr_show_quiz_result', true ) ) return;
        if( $ques = lpr_get_question( $question_id ) ) {
            $quiz_answers = learn_press_get_question_answers(null, $quiz->ID);
            $answer = isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : array();
            switch( $ques->get_type() ) {
                case 'multi_choice':
                    ?>
                    <ul class="lpr-question-hint">
                        <?php if ($answers = $ques->get('options.answer')) foreach ($answers as $k => $ans):
                            $classes = array();
                            if (in_array($k, $answer)) {
                                if ($ques->get("options.answer.{$k}.is_true")) {
                                    $classes[] = "correct";
                                } else {
                                    $classes[] = "wrong";
                                }
                            } else if ($ques->get("options.answer.{$k}.is_true")) {
                                $classes[] = "correct";
                            }
                            ?>
                            <li <?php echo $classes ? 'class="' . join(" ", $classes) . '"' : '';?>>
                                <label>
                                    <input type="checkbox"
                                           disabled="disabled" <?php checked(in_array($k, $answer) ? 1 : 0);?> />
                                    <?php echo $ques->get("options.answer.{$k}.text");?>
                                </label>
                            </li>
                        <?php endforeach;?>
                    </ul>
                    <?php
                    break;
                case 'single_choice':
                    ?>
                    <ul class="lpr-question-hint">
                        <?php if ($answers = $ques->get('options.answer')) foreach ($answers as $k => $ans):
                            $classes = array();
                            if ($k == $answer) {
                                if ($ques->get("options.answer.{$k}.is_true")) {
                                    $classes[] = "correct";
                                } else {
                                    $classes[] = "wrong";
                                }
                            } else if ($ques->get("options.answer.{$k}.is_true")) {
                                $classes[] = "correct";
                            }
                            ?>
                            <li <?php echo $classes ? 'class="' . join(" ", $classes) . '"' : '';?>>
                                <label>
                                    <input type="radio" disabled="disabled" <?php checked($k == $answer ? 1 : 0);?> />
                                    <?php echo $ques->get("options.answer.{$k}.text");?>
                                </label>
                            </li>
                        <?php endforeach;?>
                    </ul>
                    <?php
                    break;
                case 'true_or_false':
                    ?>
                    <ul class="lpr-question-hint">
                    <?php
                    for ($k = 0; $k < 2; $k++) {
                        $classes = array();
                        if ($k == $answer) {
                            if ($ques->get("options.answer.{$k}.is_true")) {
                                $classes[] = "correct";
                            } else {
                                $classes[] = "wrong";
                            }
                        } else if ($ques->get("options.answer.{$k}.is_true")) {
                            $classes[] = "correct";
                        }
                        ?>
                        <li <?php echo $classes ? 'class="' . join(" ", $classes) . '"' : '';?>>
                            <label>
                                <input type="radio" disabled="disabled" <?php checked($answer == $k ? 1 : 0); ?> />
                                <?php echo $ques->get('options.answer.'.$k.'.text');?>
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

        }
    }
}

if( ! function_exists( 'learn_press_course_lesson_class' ) ){
    /**
     * The class of lesson in course curriculum
     *
     * @param int $lesson_id
     * @param array|string $class
     */
    function learn_press_course_lesson_class( $lesson_id = null, $class = null ){
        if( is_string( $class ) && $class ) $class = preg_split('!\s+!', $class );
        else $class = array();

        $classes = array(
            'course-lesson'
        );
        if( learn_press_user_has_completed_lesson( $lesson_id ) ){
            $classes[] = "completed";
        }
        if( $lesson_id && ! empty( $_REQUEST['lesson'] ) && ( $lesson_id ==  $_REQUEST['lesson']) ){
            $classes[] = 'current';
        }
        $classes = array_unique( array_merge( $classes, $class ) );
        echo 'class="' . implode( ' ', $classes ) . '"';
    }
}

if( ! function_exists( 'learn_press_course_quiz_class' ) ){
    /**
     * The class of lesson in course curriculum
     *
     * @param int $quiz_id
     * @param string|array $class
     */
    function learn_press_course_quiz_class( $quiz_id = null, $class = null ){
        if( is_string( $class ) && $class ) $class = preg_split('!\s+!', $class );
        else $class = array();

        $classes = array(
            'course-quiz'
        );
        if( learn_press_user_has_completed_quiz( null, $quiz_id ) ){
            $classes[] = "completed";
        }
        $classes = array_unique( array_merge( $classes, $class ) );
        echo 'class="' . join( ' ', $classes ) . '"';
    }
}

if( ! function_exists( 'learn_press_message' ) ){
    /**
     * Template to display the messages
     * @param $content
     * @param string $type
     */
    function learn_press_message( $content, $type = 'message' ){
        learn_press_get_template( 'global/message.php', array( 'type' => $type, 'content' => $content ) );
    }
}

if( ! function_exists( 'learn_press_course_content_summary' ) ){
    /**
     * Display the content of a lesson in a course content
     * @return int
     */
    function learn_press_course_content_summary(){
        $lesson_id = isset( $_GET['lesson'] ) ? $_GET['lesson'] : '';
        global $post;
        // ensure that we are passing the lesson correctly
        if( $lesson_id && ( 'lpr_lesson' == get_post_type( $lesson_id ) ) && ( $lesson = get_post( $lesson_id ) ) ){
            //check if user enrolled this course or not
            $course_id = get_the_ID();
            $user_id = get_current_user_id();
            $user_courses = learn_press_get_user_courses( $user_id );            
            $enrolled = false;
            if( isset( $user_courses ) && is_array( $user_courses ) ) {
                $enrolled = in_array( $course_id, $user_courses );
            }                        
            //if( !$enrolled && ! learn_press_is_lesson_preview( $lesson_id ) ) {
            if( ! learn_press_user_can_view_lesson( $lesson_id ) ){
                echo "You have to enrolled to see lesson content";
                do_action('learn_press_course_content_course');
                return 0;             
            }

            // setup lesson as global post so we can uses the template function as in the loop
            $post = $lesson;
            setup_postdata( $post );
            do_action( 'learn_press_course_content_lesson', $post );

            // now reset the post to the course
            wp_reset_postdata();
        }else{
            do_action( 'learn_press_course_content_course' );
        }
    }
}

if( ! function_exists( 'learn_press_course_content_course_title' ) ){
    /**
     * Display the title of a course in single page
     */
    function learn_press_course_content_course_title(){
        learn_press_get_template( "course/content-course-title.php" );
    }
}

if( ! function_exists( 'learn_press_course_content_course_description' ) ){
    /**
     * Display the description of a course
     */
    function learn_press_course_content_course_description(){
        learn_press_get_template( "course/content-course-description.php" );
    }
}

if( ! function_exists( 'learn_press_course_content_lesson_title' ) ){
    /**
     * Display the title of a lesson in single course
     */
    function learn_press_course_content_lesson_title(){
        learn_press_get_template( "course/content-lesson-title.php" );
    }
}

if( ! function_exists( 'learn_press_course_content_lesson_description' ) ){
    /**
     * Display the description of a lesson in single course page
     */
    function learn_press_course_content_lesson_description(){
        learn_press_get_template( "course/content-lesson-description.php" );
    }
}

if( ! function_exists( 'learn_press_course_content_lesson_action' ) ){
    /**
     * Display the "Complete button" if user hasn't completed lesson
     * Otherwise, display the message says that they has finished
     */
    function learn_press_course_content_lesson_action(){
        // we are in lesson not course
        if( learn_press_user_has_completed_lesson() ){
            _e( 'Congratulations! You have completed this lesson.', 'learn_press' );
        }else {
            $course_id = learn_press_get_course_by_lesson( get_the_ID() );
            if( ! learn_press_user_has_finished_course( $course_id ) && learn_press_user_has_enrolled_course( $course_id ) ) {
                printf( '<button class="complete-lesson-button" data-id="%d">%s</button>', get_the_ID(), __( 'Complete Lesson', 'learn_press' ) );
            }
        }
    }
}

if( ! function_exists( 'learn_press_course_content_next_prev_lesson' ) ){
    /**
     * Display the next/previous buttons to move the lesson to next or previous
     */
    function learn_press_course_content_next_prev_lesson(){

        // we are not in the loop of the course
        // we are in the loop of the lesson
        $course_id = learn_press_get_course_by_lesson( get_the_ID() );
        if( ! learn_press_user_has_enrolled_course( $course_id ) ) return;

        $lessons = learn_press_get_lessons_in_course( $course_id );
        $current_position = array_search( get_the_ID(), $lessons );

        $prev = null;
        $next = null;

        if( 1 < ( $n = sizeof( $lessons ) ) ){
            if ( $current_position < sizeof( $lessons ) - 1 ) {
                $next_position  = $current_position + 1;
                $next_id        = $lessons[$next_position];
                $button_text    = get_the_title( $next_id );
                $button_text    = apply_filters( 'learn_press_course_content_next_button_text', $button_text, $next_id, $course_id );
                $next = sprintf( '<a href="%s" class="next-lesson" data-id="%d">%s</a>', learn_press_get_course_lesson_permalink($next_id, $course_id), $next_id, $button_text );
            }
            if ( $current_position > 0 ) {
                $prev_position  = $current_position - 1;
                $prev_id        = $lessons[$prev_position];
                $button_text    = get_the_title( $prev_id );
                $button_text    = apply_filters( 'learn_press_course_content_prev_button_text', $button_text, $prev_id, $course_id );
                $prev = sprintf( '<a href="%s" class="prev-lesson" data-id="%d">%s</a>', learn_press_get_course_lesson_permalink($prev_id, $course_id), $prev_id, $button_text );
            }
            $args = compact( 'prev_id', 'next_id', 'prev', 'next');
            do_action( 'learn_press_before_course_content_lesson_nav', $args );
            printf( '<p class="course-content-lesson-nav">%s%s</p>', $prev, $next );
            do_action( 'learn_press_after_course_content_lesson_nav', $args );
        }
    }
}

function learn_press_before_course_content_lesson_nav( $args ){
    $args = wp_parse_args(
        $args,
        array(
            'prev_id'       => null,
            'next_id'       => null,
            'prev'      => null,
            'next'      => null
        )
    );
    echo '
        <p class="course-content-lesson-nav-text">
            ' . ($args['prev_id'] ? '<span class="prev-lesson-text">'.__('Previous', 'learn_press').'</span>' : '') . '
            ' . ($args['next_id'] ? '<span class="next-lesson-text">'.__('Next', 'learn_press').'</span>' : '') . '
        </p>
    ';
}

if( ! function_exists( 'learn_press_get_course_lesson_permalink' ) ){
    /**
     * get the permalink of lesson in course curriculum
     *
     * @param int $lesson_id
     * @param int $course_id
     */
    function learn_press_get_course_lesson_permalink( $lesson_id, $course_id = null ){
        if( ! $course_id ) $course_id = get_the_ID();
        $permalink = get_the_permalink( $course_id );
        if( false !== strpos( $permalink, '?' ) ) {
            $permalink = $permalink . '&lesson=' . $lesson_id;
        }else{
            $permalink = $permalink . '?lesson=' . $lesson_id;
        }
        //print_r($_REQUEST);
        //$permalink .= '&xx=1';
        return apply_filters( 'learn_press_course_lesson_permalink', $permalink, $lesson_id, $course_id );
    }
}

function learn_press_course_lesson_quiz_before_title( $lesson_or_quiz, $enrolled ){
    global $learn_press_lesson_quiz_tooltips;
    if( ! $learn_press_lesson_quiz_tooltips ) $learn_press_lesson_quiz_tooltips = array();
    if( ! empty( $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] ) ) return;
    if( !$enrolled ){
        if( 'lpr_quiz' == get_post_type( $lesson_or_quiz ) ) {
            $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] = array( 'message' => __('This is a quiz. Please enroll to do this quiz', 'learn_press') );
        }else{
            $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] = array( 'message' => __('This is a lesson. Please enroll to study this lesson', 'learn_press') );
        }
    }else{
        if( 'lpr_quiz' == get_post_type( $lesson_or_quiz ) ) {

            if( learn_press_user_has_started_quiz( null, $lesson_or_quiz ) ){
                $result = learn_press_get_quiz_result( null, $lesson_or_quiz );
                $tooltip = sprintf(
                    __( '%s and answered correctly %d out of %d total questions', 'learn_press' ),
                    learn_press_user_has_completed_quiz( null, $lesson_or_quiz ) ? __( 'You have finished this quiz', 'learn_press' ) : __( 'You have started this quiz', 'learn_press' ),
                    $result['correct'],
                    $result['questions_count']
                );
            }else{
                $tooltip = __( 'This is a quiz. Click on link to complete this quiz', 'learn_press' );
            }
            $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] = array( 'message' => $tooltip );
        }else{
            if( learn_press_user_has_completed_lesson( $lesson_or_quiz ) ) {
                $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] = array( 'completed' => __('Congratulations! You have completed this lesson', 'learn_press' ) );
            }else{
                $learn_press_lesson_quiz_tooltips[$lesson_or_quiz] = array(
                    'current' => __('You are studying this lesson', 'learn_press'),
                    'message' => __('This is a lesson. Click on link to complete this lesson', 'learn_press' )
                );
            }
        }
    }

    if( 'lpr_quiz' == get_post_type( $lesson_or_quiz ) ) {
        echo '<span class="lesson-quiz-icon quiz"></span>';
    }else{
        echo '<span class="lesson-quiz-icon lesson"></span>';
    }
}
add_action( 'learn_press_course_lesson_quiz_before_title', 'learn_press_course_lesson_quiz_before_title', 10, 2);

function learn_press_print_lesson_quiz_tooltips(){
    global $learn_press_lesson_quiz_tooltips;
    if( $learn_press_lesson_quiz_tooltips ){
        echo '<div id="lesson-quiz-tooltip-container">';
        foreach( $learn_press_lesson_quiz_tooltips as $id => $content ){
            if( is_array( $content ) ){
                foreach( $content as $k => $c ){
                    ?>
                    <div id="lesson-quiz-tip-<?php echo $k, '-', $id;?>">
                        <div class="lesson-quiz-tooltip">
                            <?php echo $c;?>
                        </div>
                    </div>
                    <?php
                }
            }else {
                ?>
                <div id="lesson-quiz-tip-message-<?php echo $id;?>">
                    <div class="lesson-quiz-tooltip">
                        <?php echo $content;?>
                    </div>
                </div>
                <?php
            }
        }
        echo '</div>';
    }
}
add_action( 'wp_footer', 'learn_press_print_lesson_quiz_tooltips' );

function learn_press_quick_lesson_link_process( $content ){
    global $post;
    $pattern = '@l([0-9]+)';
    return preg_replace_callback( "/$pattern/s", 'learn_press_quick_lesson_link_process_part', $content );
}
function learn_press_quick_lesson_link_process_part($m){
    $lesson_id = $m ? $m[1] : 0;
    if( $lesson_id ){
        $link = get_permalink( $lesson_id );
        $title = get_the_title( $lesson_id );
        return sprintf('<a href="%s">%s</a>', $link, $title);
    }
    return null;
}
add_filter( 'the_content', 'learn_press_quick_lesson_link_process', 1000 );

/**
 * Shortcode function to display the link of a lesson in the lesson content
 *
 * @param $atts
 * @param null $content
 * @return string
 */
function learn_press_quick_lesson_link_shortcode( $atts, $content = null ){
    if( 'lpr_lesson' == get_post_type() ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'text' => null,
                'target' => ''
            ), $atts
        );
        if( ( 'lpr_lesson' == get_post_type($atts['id']) ) && ( $course_id = learn_press_get_course_by_lesson( $atts['id'] ) ) ) {
            $link = learn_press_get_course_lesson_permalink( $atts['id'], $course_id );
            $text = $atts['text'] ? $atts['text'] : get_the_title($atts['id']);
            $target = $atts['target'];
            return sprintf('<a href="%s" target="%s">%s</a>', $link, $target, $text);
        }
        return '';
    }
    return '';
}
add_shortcode( 'quick_lesson_link', 'learn_press_quick_lesson_link_shortcode' );

/**
 * Modify the page title depending on where we are standing in single course
 * Page title = COURSE_TITLE::LESSON_TITLE
 *
 * @param $title
 * @param $sep
 * @return mixed
 */
function learn_press_page_title( $title, $sep ){
    if ( is_feed() || ( 'lpr_course' != get_post_type() ) || empty( $_REQUEST['lesson'] ) ){
        return $title;
    }
    $course_title = get_the_title();
    learn_press_enqueue_script('window.learn_press_title_sep=\'' . $sep . '\'');
    return preg_replace('!(' . $course_title . ')!', '$1::' . get_the_title( $_REQUEST['lesson'] ), $title );
}
add_filter( 'wp_title', 'learn_press_page_title', 10, 2 );

/**
 * LearnPress Embed Video button
 */
function learn_press_embed_video_button() {
    add_filter( 'mce_external_plugins', 'learn_press_add_buttons' );
    add_filter( 'mce_buttons', 'learn_press_register_buttons' );
}
add_action( 'init', 'learn_press_embed_video_button' );

/**
 * Adđ embed button 
 */
function learn_press_add_buttons( $plugin_array ) {
    $plugin_array[ 'embed' ] = LPR_PLUGIN_URL . '/assets/js/learnpress-embed-button.js';

    return $plugin_array;
}

/**
 * Register embed button
 */
function learn_press_register_buttons( $buttons ) {
    array_push( $buttons , 'embed');
    return $buttons;
}

/**
 * Embed video shortcode 
 */

function learn_press_embed_video_shortcode( $atts ) {
    $a = shortcode_atts(array(
            'link' => ''
        ), $atts);
    $embed_link = wp_oembed_get($a['link']);
    $html = '<div class="videoWrapper" itemprop="video" itemscope itemtype="http://schema.org/VideoObject">';
    $html .= $embed_link;
    $html .= '</div>';
    return $html;
}
add_shortcode( 'embed_video', 'learn_press_embed_video_shortcode' );

/**
 * Custom embed video 
 */
function learn_press_custom_embed_video($html, $url, $attr, $post_ID) {
    $return = '<div class="videoWrapper" itemprop="video" itemscope itemtype="http://schema.org/VideoObject">' . $html . '</div>';
    return $return;
}
add_filter( 'embed_oembed_html', 'learn_press_custom_embed_video', 10, 4 );