<?php

/**
 * Class LPR_Settings_Pages
 */
class LPR_Settings_Pages extends LPR_Settings_Base{
    function __construct(){
        $this->id   = 'pages';
        $this->text = __( 'Pages', 'learn_press' );

        if( $sections = $this->get_sections() ) foreach( $sections as $id => $text ){
            add_action( 'learn_press_section_' . $this->id . '_' . $id, array( $this, 'output_section_' . $id ) );
        }
        parent::__construct();
    }

    function get_sections(){
        $sections = array(
            'general'       => __( 'General', 'learn_press' )/*,
            'course'        => __( 'Course', 'learn_press' ),
            'quiz'          => __( 'Quiz', 'learn_press' )*/
        );
        return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
    }

    function output(){
        $section = $this->section;
        do_action( 'learn_press_section_' . $this->id . '_' . $this->section['id'] );
    }

    function output_section_general(){
        $settings = LPR_Admin_Settings::instance( 'pages' );
        $dropdown_pages = learn_press_pages_dropdown(
            '{NAME}',
            $settings->get('general.courses_page_id', 0),
            array(
                'id' => '{ID}',
                'before' => array(
                    'add_new_page' => __( '[ Add a new page ]', 'learn_press' )
                ),
                'class' => 'lpr-dropdown-pages',
                'echo'  => false
            )
        );
        ?>

        <h3 class=""><?php echo $this->section['text'];?></h3>
        <table class="form-table">
            <tbody>
            <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
            <tr>
                <th scope="row"><label><?php _e( 'Courses Page', 'learn_press' );?></label></th>
                <td>
                    <?php $page_id = $settings->get('general.courses_page_id', 0);?>
                    <?php echo preg_replace( array( '!{NAME}!', '!{ID}!' ), array( "lpr_settings[" . $this->id . "][courses_page_id]", 'lpr_courses_page_id' ), $dropdown_pages );?>
                    <p id="lpr_course_page_id_form" class="lpr-quick-add-page-inline hide-if-js">
                        <input type="text" />
                        <button class="button" type="button"><?php _e( 'Ok', 'learn_press' );?></button>
                        <a href=""><?php _e( 'Cancel', 'learn_press' );?></a>
                    </p>
                    <p class="lpr-quick-actions-inline<?php echo $page_id ? '' : ' hide-if-js';?>">
                        <a href="<?php echo get_edit_post_link( $page_id );?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' );?></a>
                        <a href="<?php echo get_permalink( $page_id );?>" target="_blank"><?php _e( 'View Page', 'learn_press' );?></a>
                    </p>
                </td>
            </tr>
            <!--
            <tr>
                <th scope="row"><label><?php _e( 'Profile Page', 'learn_press' );?></label></th>
                <td>
                    <?php learn_press_pages_dropdown( "lpr_settings[" . $this->id . "][profile_page_id]", $settings->get('general.profile_page_id', 0));?>
                    <?php if( $page_id = $settings->get('general.profile_page_id', 0) ){?>
                        <a href="<?php echo get_edit_post_link( $page_id );?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' );?></a>
                        <a href="<?php echo get_permalink( $page_id );?>" target="_blank"><?php _e( 'View Page', 'learn_press' );?></a>
                    <?php }?>
                </td>
            </tr>
            -->
            <tr>
                <th scope="row"><label><?php _e( 'Take Course Confirm', 'learn_press' );?></label></th>
                <td>
                    <?php
                    $page_id = $settings->get('general.taken_course_confirm_page_id', 0);
                    $output = preg_replace( array( '!{NAME}!', '!{ID}!' ), array( "lpr_settings[" . $this->id . "][taken_course_confirm_page_id]", 'lpr_taken_course_confirm_page_id' ), $dropdown_pages );
                    $output = preg_replace( '!selected="selected"!', '', $output );
                    $output = preg_replace( '!(value="' . $page_id . '")!', '$1 selected="selected"', $output );
                    echo $output;
                    ?>
                    <?php //learn_press_pages_dropdown( "lpr_settings[" . $this->id . "][taken_course_confirm_page_id]", $settings->get('general.taken_course_confirm_page_id', 0));?>
                    <p class="lpr-quick-add-page-inline hide-if-js">
                        <input type="text" />
                        <button class="button" type="button"><?php _e( 'Ok', 'learn_press' );?></button>
                        <a href=""><?php _e( 'Cancel', 'learn_press' );?></a>
                    </p>
                    <p class="lpr-quick-actions-inline<?php echo $page_id ? '' : ' hide-if-js';?>">

                        <a href="<?php echo get_edit_post_link( $page_id );?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' );?></a>
                        <a href="<?php echo get_permalink( $page_id );?>" target="_blank"><?php _e( 'View Page', 'learn_press' );?></a>
                    </p>
                </td>
            </tr>
            <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
            </tbody>
        </table>
    <?php
    }
    function output_section_course(){
        $settings = LPR_Admin_Settings::instance( 'pages' );
    ?>
    <h3 class=""><?php echo $this->section['text'];?></h3>
    <table class="form-table">
        <tbody>
        <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
        <tr>
            <th scope="row"><label><?php _e( 'Retake course', 'learn_press' );?></label></th>
            <td>
                <input type="number" name="lpr_settings[<?php echo $this->id;?>][retake_course]" value="<?php echo $settings->get('course.retake_course', 0);?>" />
                <p class="description"><?php _e( 'The number of times a user can re-take course. Set to 0 to disabled', 'learn_press' ) ;?></p>
            </td>
        </tr>
        <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
        </tbody>
    </table>
    <?php
    }

    function output_section_quiz(){
        $settings = LPR_Admin_Settings::instance( 'pages' );
    ?>
    <h3 class=""><?php echo $this->section['text'];?></h3>
    <table class="form-table">
        <tbody>
        <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
        <tr>
            <th scope="row"><label><?php _e( 'Retake quiz', 'learn_press' );?></label></th>
            <td>
                <input type="number" name="lpr_settings[<?php echo $this->id;?>][retake_quiz]" value="<?php echo $settings->get('quiz.retake_quiz', 0);?>" />
                <p class="description"><?php _e( 'The number of times a user can re-take quiz. Set to 0 to disabled', 'learn_press' ) ;?></p>
            </td>
        </tr>
        <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
        </tbody>
    </table>
    <?php
    }

    function save(){
        $settings = LPR_Admin_Settings::instance( 'pages' );
        $section = $this->section['id'];
        if( $section == 'quiz' ){
            $post_data = $_POST['lpr_settings'][$this->id];
            if( $post_data['retake_quiz'] < 0 ) $post_data['retake_quiz'] = 0;
            $settings->set( 'quiz', $post_data );
        }elseif( 'course' == $section ){
            $post_data = $_POST['lpr_settings'][$this->id];
            if( $post_data['retake_course'] < 0 ) $post_data['retake_course'] = 0;
            $settings->set( 'course', $post_data );
        }else{
            $post_data = $_POST['lpr_settings'][$this->id];
            $settings->set( 'general', $post_data );
        }
        $settings->update();
    }
}
new LPR_Settings_Pages();