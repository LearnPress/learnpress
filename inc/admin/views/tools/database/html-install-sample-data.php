<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

$section_range  = LP_Install_Sample_Data::$section_range;
$item_range     = LP_Install_Sample_Data::$item_range;
$question_range = LP_Install_Sample_Data::$question_range;
$answer_range   = LP_Install_Sample_Data::$answer_range;
?>
<div class="card">
    <h2><?php _e( 'Install Sample Data', 'learnpress' ); ?></h2>
    <p><?php _e( 'Create a <strong>Sample course</strong> with lessons and quizzes. The content will be filled with <strong>Lorem</strong> text.', 'learnpress' ); ?></p>
    <fieldset class="lp-install-sample-data-options hide-if-js">
        <legend><?php esc_html_e( 'Options', 'learnpress' ); ?></legend>

        <ul>
            <li>
                <p><?php esc_html_e( 'Course name', 'learnpress' ); ?></p>
                <input type="text" class="widefat" name="custom-name" value=""
                          placeholder="<?php esc_attr_e( 'Sample course', 'learnpress' ); ?>">
            </li>
            <li>
                <p><?php esc_html_e( 'Random number of sections in range', 'learnpress' ); ?></p>
                <input type="number" size="3" value="<?php echo $section_range[0]; ?>" min="1" max="20"
                       name="section-range[]">
                <input type="number" size="3" value="<?php echo $section_range[1]; ?>" min="1" max="20"
                       name="section-range[]">
            </li>
            <li>
                <p><?php esc_html_e( 'Random number of items in range (each section)', 'learnpress' ); ?></p>
                <input type="number" size="3" value="<?php echo $item_range[0]; ?>" min="1" max="50"
                       name="item-range[]">
                <input type="number" size="3" value="<?php echo $item_range[1]; ?>" min="1" max="50"
                       name="item-range[]">
            </li>
            <li>
                <p><?php esc_html_e( 'Random number of questions in range (each quiz)', 'learnpress' ); ?></p>
                <input type="number" size="3" value="<?php echo $question_range[0]; ?>" min="1" max="50"
                       name="question-range[]">
                <input type="number" size="3" value="<?php echo $question_range[1]; ?>" min="1" max="50"
                       name="question-range[]">
            </li>
            <li>
                <p><?php esc_html_e( 'Random number of answers in range (each question)', 'learnpress' ); ?></p>
                <input type="number" size="3" value="<?php echo $answer_range[0]; ?>" min="1" max="10"
                       name="answer-range[]">
                <input type="number" size="3" value="<?php echo $answer_range[1]; ?>" min="1" max="10"
                       name="answer-range[]">
            </li>
        </ul>
    </fieldset>

    <p class="tools-button">
        <a class="button" id="learn-press-install-sample-data"
           data-text="<?php esc_attr_e( 'Install', 'learnpress' ); ?>"
           data-installing-text="<?php esc_attr_e( 'Installing...', 'learnpress' ); ?>"
           href="<?php echo wp_nonce_url( admin_url( 'index.php?page=lp-install-sample-data' ), 'install-sample-course' ); ?>">
			<?php esc_html_e( 'Install', 'learnpress' ); ?>
        </a>
        <a href="" id="learn-press-install-sample-data-options"><?php esc_html_e( 'Show options', 'learnpress' ); ?></a>
        <a class="button"
           data-text="<?php esc_attr_e( 'Delete sample course', 'learnpress' ); ?>"
           data-uninstalling-text="<?php esc_attr_e( 'Deleting...', 'learnpress' ); ?>"
           id="learn-press-uninstall-sample-data"
           href="<?php echo wp_nonce_url( admin_url( 'index.php?page=lp-uninstall-sample-data' ), 'uninstall-sample-course' ); ?>">
			<?php _e( 'Delete sample course', 'learnpress' ); ?>
        </a>
    </p>
</div>