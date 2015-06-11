<?php
/**
 * Created by PhpStorm.
 * User: Tu
 * Date: 27/03/2015
 * Time: 11:42 SA
 * Modified 03 Apr 2015
 */
class LPR_Question_Type_True_Or_False extends LPR_Question_Type{
    function __construct( $type = null, $options = null ){
        parent::__construct( $type, $options );


    }

    function submit_answer( $quiz_id, $answer ){
        $questions = learn_press_get_question_answers( null, $quiz_id );
        if( !is_array( $questions ) ) $questions = array();
        $questions[$quiz_id][$this->get('ID')] = is_array( $answer ) ? reset( $answer ) : $answer;
        learn_press_save_question_answer( null, $quiz_id, $this->get('ID'), is_array( $answer ) ? reset( $answer ) : $answer);
    }

    function admin_interface( $args = array() ){
        $uid = uniqid( 'lpr_question_answer' );
        $post_id = $this->get('ID');
        $this->admin_interface_head( $args );
    ?>
    <table class="lpr-sortable lpr-question-option">
        <thead>
            <th width="20"></th>
            <th><?php _e('Is Correct?', 'learnpress');?></th>
            <th><?php _e('Answer Text', 'learnpress');?></th>
        </thead>
        <tbody>
            <tr>
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <th class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__0]" value="<?php echo $this->get('options.answer.0.is_true', 1);?>" />
                    <input data-group="lpr-question-answer-<?php echo $this->get('ID');?>" type="radio" <?php checked( $this->get('options.answer.0.is_true', 1) );?> />

                </th>
                <td><input class="lpr-answer-text" type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__0]" value="<?php echo esc_attr( $this->get( 'options.answer.0.text', __( 'True', 'learnpres' ) ) );?>" /></td>
            </tr>
            <tr>
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <th class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__1]" value="<?php echo $this->get('options.answer.1.is_true', 0);?>" />
                    <input data-group="lpr-question-answer-<?php echo $this->get('ID');?>" type="radio" <?php checked( $this->get('options.answer.1.is_true', 0) );?> />

                </th>
                <td><input class="lpr-answer-text" type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__1]" value="<?php echo esc_attr( $this->get( 'options.answer.1.text', __( 'False', 'learnpres' ) ) );?>" /></td>
            </tr>
        </tbody>
    </table>
    <?php
        $this->admin_interface_foot( $args );
    }

    function render( $args = array() ){
        $unique_name = uniqid( 'lp_question_answer_' . $this->get('ID') . '_' );
        $answer = null;
        is_array( $args ) && extract( $args );

    ?>
        <div class="lp-question-wrap question-<?php echo $this->get('ID');?>">
            <h4><?php echo get_the_title( $this->get('ID') );?></h4>

            <ul>
                <li>
                    <label>
                        <input type="radio" name="<?php echo $unique_name;?>" <?php checked( strlen( $answer) && !$answer ? 1 : 0); ?> value="0">
                        <?php echo $this->get('options.answer.0.text');?>
                    </label>
                </li>
                <li>
                    <label>
                        <input type="radio" name="<?php echo $unique_name;?>" <?php checked( $answer == 1 ? 1 : 0); ?> value="1">
                        <?php echo $this->get('options.answer.1.text');?>
                    </label>
                </li>
            </ul>
        </div>
    <?php
    }

    function save_post_action(){

        if( $post_id = $this->get('ID') ){
            $post_data = isset( $_POST['lpr_question'] ) ? $_POST['lpr_question'] : array();
            $post_answers = array();
            if( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ){

                //if( 'lpr_question' != get_post_type( $post_id ) ){
                    try {
                        $ppp = wp_update_post(
                            array(
                                'ID' => $post_id,
                                'post_title' => $post_data['text'],
                                'post_type' => 'lpr_question'
                            )
                        );
                    }catch ( Exception $ex){echo "ex:";print_r($ex);}

               // }else{

               // }

                $index = 0;

                foreach( $post_data['answer']['text'] as $k => $txt ){
                    $post_answers[$index++] = array(
                        'text'      => $txt,
                        'is_true'   => $post_data['answer']['is_true'][$k]
                    );
                }

            }
            $post_data['answer']    = $post_answers;
            $post_data['type']      = $this->get_type();

            update_post_meta( $post_id, '_lpr_question', $post_data );
            //print_r($post_data);
        }
        return $post_id;
       // die();
    }

    function check( $args = false ){
        $answer = false;
        is_array( $args ) && extract( $args );
        $return = array(
            'correct'   => false,
            'mark'      => 0
        );

        if( is_numeric( $answer ) ) {
            if ($this->get('options.answer.' . $answer . '.is_true') ) {
                $return['correct']  = true;
                $return['mark']     = intval( get_post_meta( $this->get('ID'), '_lpr_question_mark', true ) );
            }
        }
        return $return;
    }
}