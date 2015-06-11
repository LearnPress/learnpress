<?php

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Question_Field' ) ) {
    class RWMB_Question_Field extends RWMB_Field {
        static function admin_enqueue_scripts()
        {
            wp_enqueue_style('lpr-question', RWMB_CSS_URL . 'question.css', array(), '3.2');
            //wp_register_script('select2', RWMB_JS_URL . 'select2/select2.min.js', array(), '3.2', true);

        }
        static function add_actions() {
            // Do same actions as file field
            parent::add_actions();

            add_action( 'wp_ajax_lpr_load_question_settings', array( __CLASS__, 'load_question_settings' ) );

        }

        static function load_question_settings(){
            $type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : null;
            $question_id = isset( $_REQUEST['question_id'] ) ? $_REQUEST['question_id'] : null;

            $options = array(
                'ID'    => $question_id
            );

            $question = LPR_Question_Type::instance( $type, $options );
            $options = $question->get('options');
            if( isset( $options['type'] ) && $options['type'] == $type ){

            }else{
                unset($options['answer']);
                $question->set('options', $options);
            }

            $post_options = ! empty( $_REQUEST['options'] ) ? $_REQUEST['options'] : null;
            if( $type == 'single_choice' ) {
                $selected = -1;
                if ($post_options && $post_options['answer'] ) foreach ($post_options['answer'] as $k => $option) {
                    if (!empty($option['is_true'])) $selected = $k;
                    $post_options['answer'][$k]['is_true'] = 0;
                }
                if( $selected > -1 ){
                    $post_options['answer'][$selected]['is_true'] = 1;
                }
            }
            if( $post_options ) $question->set('options', $post_options);

            $question->admin_interface();
            die();
        }

        static function save( $new, $old, $post_id, $field ) {
            $type = $_POST['lpr_question']['type'];
            $question = LPR_Question_Type::instance( $type, array( 'ID' => $post_id ) );
            if( $question ) $question->save_post_action();

            //update_post_meta( $post_id, '_lpr_question_type', $type );

            //print_r($question);die();
        }


        static function html($meta, $field){
            global $post;
            $post_id = $post->ID;
            $questions = lpr_get_question_types();
            $question = get_post_meta( $post_id, '_lpr_question', true );
            $question = wp_parse_args(
                $question,
                array(
                    'type'  => null
                )
            );
            ob_start();
            //print_r($question_type);
        ?>
        <script type="text/javascript">var lpr_question_id = <?php echo intval($post_id);?>;</script>
        <div id="lpr-question-options-wrap">
            <select class="lpr-question-types" name="lpr_question[type]" id="lpr_question-type" data-type="<?php echo $question['type'];?>">
                <option value=""><?php _e('Select...');?></option>
            <?php if( $questions ):?>
                <?php foreach( $questions as $type ):?>
                    <option value="<?php echo $type;?>" <?php selected( ( isset( $question['type'] ) && $type == $question['type'] ) ? 1 : 0, 1);?>><?php echo LPR_Question_Type::instance( $type )->get_name();?></option>
                <?php endforeach;?>
            <?php endif;?>
            </select>
            <div class="lpr-question-settings">
            <?php if( isset( $question['type'] ) ){?>
            <?php LPR_Question_Type::instance( $question['type'], array('ID' => $post_id) )->admin_interface();?>
            <?php } ?>
            </div>
            <strong>Tips:</strong>
            <p><i>In <strong>Answer text</strong>, press Enter/Tab key to move to next</i></p>
            <p><i>In <strong>Answer text</strong>, when the text is empty press Delete/Back Space or click out side to remove</i></p>
            <p><i>In <strong>Answer text</strong>, press ESC to restore the text at the last time edited</i></p>
        </div>
        <script type="text/javascript">

        </script>
        <?php
            return ob_get_clean();
        }
    }
}