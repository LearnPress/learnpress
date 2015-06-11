<?php

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Quiz_Question_Field' ) ) {
    class RWMB_Quiz_Question_Field extends RWMB_Field {
        function __construct(){

        }
        static function admin_enqueue_scripts()
        {
            $q = new LPR_Question_Type();
            $q->admin_script();
            LPR_Admin_Assets::enqueue_style( 'select2', RWMB_CSS_URL . 'select2/select2.css'  );
            LPR_Admin_Assets::enqueue_script( 'select2', RWMB_JS_URL . 'select2/select2.min.js' );
            LPR_Admin_Assets::enqueue_script( 'lpr-quiz-question', LearnPress()->plugin_url( 'inc/admin/meta-boxes/js/quiz-question.js' ) );

        }
        static function add_actions() {
            // Do same actions as file field
            parent::add_actions();

            add_action( 'wp_ajax_lpr_quiz_question_add', array( __CLASS__, 'quiz_question_add' ) );
            add_action( 'wp_ajax_lpr_quiz_question_remove', array( __CLASS__, 'quiz_question_remove' ) );
        }

        static function quiz_question_remove(){
            $question_id    = isset( $_REQUEST['question_id'] ) ? $_REQUEST['question_id'] : null;
            $quiz_id        = isset( $_REQUEST['quiz_id'] ) ? $_REQUEST['quiz_id'] : null;

            $questions = get_post_meta($quiz_id, '_lpr_quiz_questions', true);
            if( isset( $questions[$question_id] ) ){
                unset( $questions[$question_id] );
                update_post_meta($quiz_id, '_lpr_quiz_questions', $questions);
            }
            die();
        }

        static function quiz_question_add(){
            $type           = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : null;
            $text           = isset( $_REQUEST['text'] ) ? $_REQUEST['text'] : null;
            $question_id    = isset( $_REQUEST['question_id'] ) ? $_REQUEST['question_id'] : null;
            $question       = LPR_Question_Type::instance( $question_id ? $question_id : $type );
            $json           = array(
                'success' => false
            );
            if( $question ) {
                if (!$question_id) {
                    $question->set('post_title', $text ? $text : 'Your question text here');
                    $question->set('post_type', 'lpr_question');
                    $question->set('post_status', 'publish');
                }


                if (($question_id = $question->store()) && isset($_POST['quiz_id']) && ($quiz_id = $_POST['quiz_id'])) {
                    $quiz_questions = (array)get_post_meta($quiz_id, '_lpr_quiz_questions', true);
                    $quiz_questions[$question_id] = array( 'toggle' => 0 );
                    update_post_meta($quiz_id, '_lpr_quiz_questions', $quiz_questions);
                }
                ob_start();
                $question->admin_interface();
                $json['html'] = ob_get_clean();
                $json['success'] = true;
                $json['question'] = get_post( $question_id );
            }else{
                $json['msg'] = __( 'Can not create a question' );
            }
            wp_send_json($json);
            die();
        }

        static function save_quiz_questions( $post_id ) {
            static $has_updated;
            $questions = isset( $_POST['lpr_question'] ) ? $_POST['lpr_question'] : null;
            if( !$questions ) return;
            $postmeta = array();

            // prevent infinite loop with save_post action
            if( $has_updated ) return;
            $has_updated = true;

            foreach( $questions as $question_id => $options ) {
                $question = LPR_Question_Type::instance( $question_id );
                if( $question ){
                    $question_id =  $question->save_post_action();
                    if( $question_id ){
                        $postmeta[$question_id] = array('toggle' => $options['toggle']);
                        if( ! empty( $options['type'] ) ) {
                            $post_data = get_post_meta( $question_id, '_lpr_question', true );
                            $post_data['type']      = $options['type'];
                            update_post_meta( $question_id, '_lpr_question', $post_data );
                        }
                    }
                }
            }

            update_post_meta( $post_id, '_lpr_quiz_questions', $postmeta );
        }

        static function html($meta, $field){
            global $post;
            $post_id = $post->ID;
            $current_user   = get_current_user_id();

            $questions = lpr_get_question_types();

            $lpr_questions = (array)get_post_meta( $post_id, '_lpr_quiz_questions', true );
            $qids = array_keys( $lpr_questions );
            $qoptions = array_values( $lpr_questions );
            ob_start();
        ?>
        <script type="text/javascript">var lpr_quiz_id = <?php echo intval($post_id);?></script>
        <div id="lpr-quiz-questions-wrap">
            <p align="right" class="lpr-questions-toggle">
                <a href="" data-action="expand"><?php _e( 'Expand All', 'learn_press' );?></a>
                <a href="" data-action="collapse"><?php _e( 'Collapse All', 'learn_press' );?></a>
            </p>
            <div id="lpr-quiz-questions">
            <?php if( $qids ): $index = 0;?>
                <?php foreach( $qids as $question_id ):?>
                <?php
                    if( $question = LPR_Question_Type::instance( $question_id ) ){
                        $question->admin_interface($qoptions[$index++]);
                    }
                ?>
                <?php endforeach;?>
            <?php endif;?>
            </div>
            <p style="vertical-align: middle;">
                <div class="btn-group" id="lpr-add-new-question-type">
                    <button type="button" class="btn btn-default" data-type="single_choice"><?php _e('Add new Question', 'learnpress');?></button>
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <?php if( $questions ):?>
                            <?php foreach( $questions as $type ):?>
                                <li><a href="" rel="<?php echo $type;?>"><?php echo LPR_Question_Type::instance( $type )->get_name();?></a></li>
                            <?php endforeach;?>
                        <?php endif;?>
                    </ul>
                </div>

                -Or-
                <select class="lpr-select2" name="" id="lpr-quiz-question-select-existing" style="width:300px">
                    <option value=""><?php _e( '--Select existing question--', 'learnpress' );?></option>
                    <?php

                    $query_args = array(
                        'post_type'      => 'lpr_question',
                        'post_status'    => 'publish',
                        'author'         => $current_user,
                        'posts_per_page' => -1,
                        'post__not_in'   => $qids
                    );
                    $query      = new WP_Query( $query_args );
                    if ( $query->have_posts() ) {
                        while ( $query->have_posts() ) {
                            $p = $query->next_post();
                            echo '<option value="' . $p->ID . '" data-type="">' . $p->post_title . '</option>';
                        }
                    }
                    ?>

                </select>
            </p>
        </div>

        <?php
            return ob_get_clean();
        }
    }
    add_action( 'save_post', array( 'RWMB_Quiz_Question_Field', 'save_quiz_questions' ), 1000000 );
}