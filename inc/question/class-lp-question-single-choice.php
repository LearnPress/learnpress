<?php
/**
 * Created by PhpStorm.
 * User: Tu
 * Date: 27/03/2015
 * Time: 11:42 SA
 * Modified 03 Apr 2015
 */
class LP_Question_Single_Choice extends LP_Question{
    function __construct( $type = null, $options = null ){
        parent::__construct( $type, $options );
    }
    function submit_answer( $quiz_id, $answer ){
        $questions = learn_press_get_question_answers( null, $quiz_id );
        if( !is_array( $questions ) ) $questions = array();
        $questions[$quiz_id][$this->get('ID')] = is_array( $answer ) ? reset( $answer ) : $answer;
        learn_press_save_question_answer( null, $quiz_id, $this->get('ID'), is_array( $answer ) ? reset( $answer ) : $answer);
        //print_r($answer);
    }
    function admin_script(){
        parent::admin_script();
        ?>
        <script type="text/html" id="tmpl-single-choice-question-answer">
            <tr class="lpr-disabled">
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="0" />
                    <input type="radio" data-group="lpr-question-answer-{{data.question_id}}"  name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="1"  />

                </td>
                <td><input class="lpr-answer-text" type="text" name="lpr_question[{{data.question_id}}][answer][text][__INDEX__]" value="" /></td>
                <td align="center" class="lpr-remove-answer"><span class=""><i class="dashicons dashicons-trash"></i></span> </td>
            </tr>
        </script>
    <?php

    }

    /**
     * @param bool $enqueue
     */
    private function _admin_enqueue_script( $enqueue = true ){
        ob_start();
        $key = 'question_' . $this->get('ID');
        ?>
        <script type="text/javascript">
            (function($) {
                var $form = $('#post');
                $form.unbind('learn_press_question_before_update.<?php echo $key;?>').on('learn_press_question_before_update.<?php echo $key;?>', function () {
                    var $question = $( '.lpr-question-single-choice[data-id="<?php echo $this->get('ID');?>"]' );
                    if( $question.length ) {
                        var $input = $('.lpr-is-true-answer input[type="radio"]:checked', $question);
                        if (0 == $input.length) {
                            var message = $('.lpr-question-title input', $question).val();
                            message += ": " + '<?php _e( 'No answer added to question or you must set an answer is correct!', 'learn_press' );?>'
                            window.learn_press_before_update_quiz_message.push(message);
                            return false;
                        }
                    }
                });
            })(jQuery);
        </script>
        <?php
        $script = ob_get_clean();
        if( $enqueue ) {
            $script = preg_replace('!</?script.*>!', '', $script);
            learn_press_enqueue_script($script);
        }else{
            echo $script;
        }
    }

    function admin_interface( $args = array() ){
        $uid = uniqid( 'lpr_question_answer' );
        $post_id = $this->get('ID');
        $this->admin_interface_head( $args );
        ?>
        <table class="lpr-sortable lpr-question-option">
            <thead>
            <th width="20"></th>
            <th width="100"><?php _e('Is Correct?', 'learn_press');?></th>
            <th><?php _e('Answer Text', 'learn_press');?></th>
            <th width="40"></th>
            </thead>
            <tbody>
            <?php if( $answers = $this->get('options.answer') ): foreach( $answers as $i => $ans ):?>
                <tr>
                    <td class="lpr-sortable-handle">
                        <i class="dashicons dashicons-sort"></i>
                    </td>
                    <th class="lpr-is-true-answer">
                        <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__<?php echo $i;?>]" value="<?php echo $this->get('options.answer.'.$i.'.is_true', 0);?>" />
                        <input data-group="lpr-question-answer-<?php echo $this->get('ID');?>" type="radio" <?php checked( $this->get('options.answer.'.$i.'.is_true', 0) ? 1 : 0 );?> />

                    </th>
                    <td><input class="lpr-answer-text" type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__<?php echo $i;?>]" value="<?php echo esc_attr( $this->get( 'options.answer.'.$i.'.text', __( '', 'learnpres' ) ) );?>" /></td>
                    <td align="center" class="lpr-remove-answer"><i class="dashicons dashicons-trash"></td>
                </tr>
            <?php endforeach;endif;?>
                <tr class="lpr-disabled">
                    <td class="lpr-sortable-handle">
                        <i class="dashicons dashicons-sort"></i>
                    </td>
                    <td class="lpr-is-true-answer">
                        <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__]" value="0" />
                        <input type="radio" data-group="lpr-question-answer-<?php echo $post_id;?>"  name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__]" value="1"  />

                    </td>
                    <td><input class="lpr-answer-text" type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__]" value="" /></td>
                    <td align="center" class="lpr-remove-answer"><span class=""><i class="dashicons dashicons-trash"></i></span> </td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="lpr_question[<?php echo $post_id;?>][type]" value="<?php echo $this->get_type();?>">
        <p><button type="button" class="button lpr-button-add-answer"><?php _e('Add answer', 'learn_press');?></button> </p>
        <label><?php _e('Question Explanation', 'learn_press') ?></label>
        <?php if( $explaination = $this->get('options.explaination') ) {
        echo '<textarea rows="4" name="lpr_question['. $post_id .'][explaination]">'. $explaination .'</textarea>';
        }
    else {
        echo '<textarea rows="4" name="lpr_question['. $post_id .'][explaination]"></textarea>';
    }?>
        <?php
        $this->admin_interface_foot( $args );
        $this->_admin_enqueue_script( false );
    }

    function save_post_action(){
        if( $post_id = $this->get('ID') ){
            $post_data = isset( $_POST[LP()->question_post_type] ) ? $_POST[LP()->question_post_type] : array();
            $post_answers = array();
            $post_explain = $post_data[$post_id]['explaination'];
            if( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ){
                wp_update_post(
                    array(
                        'ID'            => $post_id,
                        'post_title'    => $post_data['text'],
                        'post_type'     => LP()->question_post_type
                    )
                );
                $index = 0;
                foreach( $post_data['answer']['text'] as $k => $txt ){
                    if( !$txt ) continue;
                    $post_answers[$index++] = array(
                        'text'      => $txt,
                        'is_true'   => $post_data['answer']['is_true'][$k]
                    );
                }
            }
            $post_data['answer']    = $post_answers;
            $post_data['type']      = $this->get_type();
            $post_data['explaination'] = $post_explain;
            update_post_meta( $post_id, '_lpr_question', $post_data );
        }
        return $post_id;
    }
    function render( $args = null ){
        $unique_name = uniqid( 'lp_question_answer_' . $this->get('ID') . '_' );
        $answer = '';
        is_array( $args ) && extract( $args );
        if( $answer )
            $answer = (array)$answer;
        else $answer = array();
        ?>
        <div class="lp-question-wrap question-<?php echo $this->get('ID');?>">
            <h4><?php echo get_the_title( $this->get('ID') );?></h4>
            <ul>
                <?php if( $answers = $this->get('options.answer') ) foreach( $answers as $k => $ans ):?>
                    <li>
                        <label>
                            <input type="radio" name="<?php echo $unique_name;?>" <?php checked( in_array( $k, $answer ) ? 1 : 0 );?> value="<?php echo $k;?>">
                            <?php echo $this->get("options.answer.{$k}.text");?>
                        </label>
                    </li>
                <?php endforeach;?>
            </ul>
            <?php 
                $question = get_post( $this->get('ID') );
                $question_content = $question->post_content;                
                if( !empty($question_content) ) :
            ?>

            <div id="question-hint" class="question-hint-wrap">
                <h5 class="question-hint-title"><?php _e('Question hint', 'learn_press');?></h5>
                <div class="question-hint-content">
                    <p><?php echo apply_filters('the_content', $question_content); ?></p>
                </div>
            </div>
            <script type="text/javascript">
                jQuery('.question-hint-content').hide();
                jQuery('#question-hint').on('click', function(){     
                    jQuery('.question-hint-content').fadeToggle();
                });
            </script>

            <?php endif; ?>   
        </div>
    <?php
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
///