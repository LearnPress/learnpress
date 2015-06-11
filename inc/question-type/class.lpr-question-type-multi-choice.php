<?php
/**
 * Created by PhpStorm.
 * User: Tu
 * Date: 27/03/2015
 * Time: 11:42 SA
 * Modified 03 Apr 2015
 */
class LPR_Question_Type_Multi_Choice extends LPR_Question_Type{
    function __construct( $type = null, $options = null ){
        parent::__construct( $type, $options );
    }
    function submit_answer( $quiz_id, $answer ){
        $questions = learn_press_get_question_answers( null, $quiz_id );
        if( !is_array( $questions ) ) $questions = array();
        $questions[$quiz_id][$this->get('ID')] = is_array( $answer ) ? reset( $answer ) : $answer;
        learn_press_save_question_answer( null, $quiz_id, $this->get('ID'), is_array( $answer ) ? reset( $answer ) : $answer);
    }
    function admin_script(){
        parent::admin_script();
        ?>
        <script type="text/html" id="tmpl-multi-choice-question-answer">
            <tr class="lpr-disabled">
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="0" />
                    <input type="checkbox"  name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="1"  />

                </td>
                <td><input class="lpr-answer-text" type="text" name="lpr_question[{{data.question_id}}][answer][text][__INDEX__]" value="" /></td>
                <td align="center" class="lpr-remove-answer"><span class=""><i class="dashicons dashicons-trash"></i></span> </td>
            </tr>
        </script>
        <?php
    }
    function admin_interface( $args = array() ){
        $uid = uniqid( 'lpr_question_answer' );
        $post_id = $this->get('ID');
        $this->admin_interface_head( $args );
    ?>
    <table class="lpr-question-option lpr-question-answer lpr-sortable">
        <thead>
            <th width="20"></th>
            <th><?php _e('Is Correct?', 'learnpress');?></th>
            <th><?php _e('Answer Text', 'learnpress');?></th>
            <th class="lpr-remove-answer" width="40"></th>
        </thead>
        <tbody>
            <?php if( $answers = $this->get('options.answer') ): foreach( $answers as $i => $ans ):?>
            <tr>
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__<?php echo $i;?>]" value="0" />
                    <input type="checkbox"  name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__<?php echo $i;?>]" value="1" <?php checked( $this->get('options.answer.'.$i.'.is_true', 0) ? 1 : 0 );?> />

                </td>
                <td><input type="text" class="lpr-answer-text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__<?php echo $i;?>]" value="<?php echo esc_attr( $this->get( 'options.answer.'.$i.'.text', __( '', 'learnpres' ) ) );?>" /></td>
                <td align="center" class="lpr-remove-answer"><i class="dashicons dashicons-trash"></td>
            </tr>
            <?php endforeach; endif;?>
            <tr class="lpr-disabled">
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__]" value="0" />
                    <input type="checkbox"  name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__]" value="1"  />

                </td>
                <td><input class="lpr-answer-text" type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__]" value="" /></td>
                <td align="center" class="lpr-remove-answer"><span class=""><i class="dashicons dashicons-trash"></i></span> </td>
            </tr>
        </tbody>
    </table>
    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][type]" value="<?php echo $this->get_type();?>">
    <p><button type="button" class="button lpr-button-add-answer"><?php _e('Add answer', 'learnpress');?></button> </p>
    <?php
        $this->admin_interface_foot( $args );
    }

    function save_post_action(){

        if( $post_id = $this->ID ){
            $post_data = isset( $_POST['lpr_question'] ) ? $_POST['lpr_question'] : array();
            $post_answers = array();
            if( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ){
                $post_args = array(
                    'ID'            => $post_id,
                    'post_title'    => $post_data['text'],
                    'post_type'     => 'lpr_question'
                );
                wp_update_post( $post_args );
                $index = 0;
                foreach( $post_data['answer']['text'] as $k => $txt ){
                    if( !$txt ) continue;
                    $post_answers[$index] = array(
                        'text'      => $txt,
                        'is_true'   => $post_data['answer']['is_true'][$k]
                    );
                    $index++;
                }
            }
            $post_data['answer']    = $post_answers;
            $post_data['type']      = $this->get_type();
            update_post_meta( $post_id, '_lpr_question', $post_data );
        }
        return intval( $post_id );
    }

    function render( $args = null ){
        $unique_name = uniqid( 'lp_question_answer_' . $this->get('ID') . '_' );
        $answer = false;
        is_array( $args ) && extract( $args );

        if( $answer ) settype( $answer, 'array' );
        else $answer = array();

    ?>
        <div class="lp-question-wrap question-<?php echo $this->get('ID');?>">
            <h4><?php echo get_the_title( $this->get('ID') );?></h4>
            <ul>
                <?php if( $answers = $this->get('options.answer') ) foreach( $answers as $k => $ans ):?>
                <li>
                    <label>
                        <input type="checkbox" name="<?php echo $unique_name;?>[]" <?php checked( in_array( $k, $answer ) ? 1 : 0 );?> value="<?php echo $k;?>">
                        <?php echo $this->get("options.answer.{$k}.text");?>
                    </label>
                </li>
                <?php endforeach;?>
            </ul>
        </div>
    <?php
    }

    function check( $args = false ){
        $answer = false;
        is_array( $args ) && extract( $args );
        $return = array(
            'correct'   => true,
            'mark'      => intval( get_post_meta( $this->get('ID'), '_lpr_question_mark', true ) )
        );
        settype( $answer, 'array' );

        if ( $answers = $this->get('options.answer') ){
            foreach( $answers as $k => $ans ){
                $is_true = $this->get('options.answer.' . $k . '.is_true') ? true : false;
                // if the option is TRUE but user did not select it => WRONG
                // or, if the option is FALSE but user selected it => WRONG
                if( ( $is_true && ! in_array( $k, $answer ) ) || ( ! $is_true && in_array( $k, $answer ) ) ){
                    $return['correct']  = false;
                    $return['mark']     = 0;
                    break;
                }
            }

        }

        return $return;
    }
}