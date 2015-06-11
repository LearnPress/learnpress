<?php
/**
 * Created by PhpStorm.
 * User: Tu
 * Date: 27/03/2015
 * Time: 11:42 SA
 */
class LPR_Question_Type_Dropdown extends LPR_Question_Type{
    function __construct( $type = null, $options = null ){
        parent::__construct( $type, $options );
    }

    function admin_script(){
        parent::admin_script();
        ?>
        <script type="text/html" id="tmpl-dropdown-question-answer">
            <tr>
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td>
                    <input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="0" />
                    <input type="radio" data-group="lpr-question-answer-{{data.question_id}}"  name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="1"  />

                </td>
                <td><input type="text" name="lpr_question[{{data.question_id}}][answer][text][__INDEX__]" value="" /></td>
                <td align="center"><span class=""><i class="dashicons dashicons-trash"></i></span> </td>
            </tr>
        </script>
    <?php
    }

    function admin_interface(){
        $uid = uniqid( 'lpr_question_answer' );
        $post_id = $this->get('ID');
        $this->admin_interface_head();
    ?>
    <table class="lpr-sortable lpr-question-option">
        <thead>
        <th width="20"></th>
        <th><?php _e('Is Correct?', 'learnpress');?></th>
        <th><?php _e('Answer Text', 'learnpress');?></th>
        <th></th>
        </thead>
        <tbody>
        <?php if( $answers = $this->get('options.answer') ): foreach( $answers as $i => $ans ):?>
            <tr>
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <th>
                    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][answer][is_true][__INDEX__<?php echo $i;?>]" value="<?php echo $this->get('options.answer.'.$i.'.is_true', 0);?>" />
                    <input data-group="lpr-question-answer-<?php echo $this->get('ID');?>" type="radio" <?php checked( $this->get('options.answer.'.$i.'.is_true', 0) );?> />

                </th>
                <td><input type="text" name="lpr_question[<?php echo $post_id;?>][answer][text][__INDEX__<?php echo $i;?>]" value="<?php echo $this->get( 'options.answer.'.$i.'.text', __( '', 'learnpres' ) );?>" /></td>
                <td align="center" class="lpr-remove-answer"><i class="dashicons dashicons-trash"></td>
            </tr>
        <?php endforeach;endif;?>
        </tbody>
    </table>
    <input type="hidden" name="lpr_question[<?php echo $post_id;?>][type]" value="<?php echo $this->get_type();?>">
    <p><button type="button" class="button lpr-button-add-answer"><?php _e('Add answer', 'learnpress');?></button> </p>
     <?php
        $this->admin_interface_foot();
    }

    function save_post_action(){
        global $post;
        if( $post_id = $this->get('ID') ){
            $post_data = isset( $_POST['lpr_question'] ) ? $_POST['lpr_question'] : array();
            $post_answers = array();
            if( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ){

                if( 'lpr_question' != get_post_type( $post->ID ) ){
                    wp_update_post(
                        array(
                            'ID'            => $post_id,
                            'post_title'    => $post_data['text'],
                            'post_type'     => 'lpr_question'
                        )
                    );
                }else{

                }


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
        }
        return $post_id;
    }
}