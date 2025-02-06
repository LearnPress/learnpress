<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

use LearnPress\Models\CoursePostModel;

defined( 'ABSPATH' ) or die();

$section_range  = LP_Install_Sample_Data::$section_range;
$item_range     = LP_Install_Sample_Data::$item_range;
$question_range = LP_Install_Sample_Data::$question_range;
$answer_range   = LP_Install_Sample_Data::$answer_range;
?>

<div class="lp-install-sample">
	<h2><?php _e( 'Install Sample Data', 'learnpress' ); ?></h2>
	<p><?php _e( 'Create a <strong>Sample course</strong> with lessons and quizzes. The content will be filled with <strong>Lorem</strong> text.', 'learnpress' ); ?></p>
	<fieldset class="lp-install-sample__options hide-if-js">
		<legend><?php _e( 'Options', 'learnpress' ); ?></legend>

		<ul>
			<li>
				<p><?php _e( 'Course name', 'learnpress' ); ?></p>
				<input type="text" class="widefat" name="custom-name" value="" placeholder="<?php esc_attr_e( 'Sample course', 'learnpress' ); ?>">
			</li>
			<li>
				<p><?php _e( 'Random number of sections in range', 'learnpress' ); ?></p>
				<input type="number" size="3" value="<?php echo esc_attr( $section_range[0] ); ?>" min="1" max="20" name="section-range[]">
				<input type="number" size="3" value="<?php echo esc_attr( $section_range[1] ); ?>" min="1" max="20" name="section-range[]">
			</li>
			<li>
				<p><?php _e( 'Random number of items in range (each section)', 'learnpress' ); ?></p>
				<input type="number" size="3" value="<?php echo esc_attr( $item_range[0] ); ?>" min="1" max="50" name="item-range[]">
				<input type="number" size="3" value="<?php echo esc_attr( $item_range[1] ); ?>" min="1" max="50" name="item-range[]">
			</li>
			<li>
				<p><?php _e( 'Random number of questions in range (each quiz)', 'learnpress' ); ?></p>
				<input type="number" size="3" value="<?php echo esc_attr( $question_range[0] ); ?>" min="1" max="50" name="question-range[]">
				<input type="number" size="3" value="<?php echo esc_attr( $question_range[1] ); ?>" min="1" max="50" name="question-range[]">
			</li>
			<li>
				<p><?php _e( 'Random number of answers in range (each question)', 'learnpress' ); ?></p>
				<input type="number" size="3" value="<?php echo esc_attr( $answer_range[0] ); ?>" min="1" max="10" name="answer-range[]">
				<input type="number" size="3" value="<?php echo esc_attr( $answer_range[1] ); ?>" min="1" max="10" name="answer-range[]">
			</li>
			<li>
				<p><?php _e( 'Course price', 'learnpress' ); ?></p>
				<input type="number" size="3" value="" min="0" name="<?php echo CoursePostModel::META_KEY_REGULAR_PRICE; ?>">
				<input type="hidden" value="all" name="<?php echo CoursePostModel::META_KEY_LEVEL; ?>">
			</li>
		</ul>
	</fieldset>

	<p class="lp-install-sample__buttons">
		<a class="button button-primary lp-install-sample__install"
			data-text="<?php esc_attr_e( 'Install', 'learnpress' ); ?>"
			data-installing-text="<?php esc_attr_e( 'Installing...', 'learnpress' ); ?>"
			href="<?php echo wp_nonce_url( admin_url( 'index.php?page=lp-install-sample-data' ), 'install-sample-course' ); ?>">
			<?php esc_html_e( 'Install', 'learnpress' ); ?>
		</a>
		<a href="" class="lp-install-sample__toggle-options"><?php esc_html_e( 'Show options', 'learnpress' ); ?></a>
		<a class="button lp-install-sample__uninstall"
			data-text="<?php esc_attr_e( 'Delete sample course', 'learnpress' ); ?>"
			data-uninstalling-text="<?php esc_attr_e( 'Deleting...', 'learnpress' ); ?>"
			href="<?php echo wp_nonce_url( admin_url( 'index.php?page=lp-uninstall-sample-data' ), 'uninstall-sample-course' ); ?>">
			<?php esc_html_e( 'Delete sample course', 'learnpress' ); ?>
		</a>
	</p>
</div>
