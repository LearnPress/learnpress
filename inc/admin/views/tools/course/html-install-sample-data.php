<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.2
 */

defined( 'ABSPATH' ) || die();

$message_install   = esc_html__( 'Are you sure you want to install the sample course data?', 'learnpress' );
$message_uninstall = esc_html__( 'Are you sure you want to delete the sample course data?', 'learnpress' );
?>

<div class="lp-install-sample">
	<h2><?php _e( 'Install Sample Data', 'learnpress' ); ?></h2>
	<p><?php _e( 'Create a <strong>Sample course</strong> with lessons and quizzes. The content will be filled with <strong>Lorem</strong> text.', 'learnpress' ); ?></p>
	<form class="lp-form-handle-sample-data lp-install-sample__options lp-hidden">
		<fieldset>
			<ul>
				<li>
					<p><?php _e( 'Course name', 'learnpress' ); ?></p>
					<input type="text" class="widefat" name="name" value="" placeholder="<?php esc_attr_e( 'Sample course', 'learnpress' ); ?>">
				</li>
				<li>
					<p>
						<?php _e( 'Random number of sections in range', 'learnpress' ); ?>
					</p>
					<input type="number" size="3" value="1" min="1" max="20" name="section_range">
					<input type="number" size="3" value="3" min="1" max="20" name="section_range">
				</li>
				<li>
					<p>
						<?php _e( 'Random number of items in range (each section)', 'learnpress' ); ?>
					</p>
					<input type="number" size="3" value="1" min="1" max="50" name="item_range">
					<input type="number" size="3" value="10" min="1" max="50" name="item_range">
				</li>
				<li>
					<p>
						<?php _e( 'Random number of questions in range (each quiz)', 'learnpress' ); ?>
					</p>
					<input type="number" size="3" value="1" min="1" max="50" name="question_range">
					<input type="number" size="3" value="5" min="1" max="50" name="question_range">
				</li>
				<li>
					<p>
						<?php _e( 'Random number of answers in range (each question)', 'learnpress' ); ?>
					</p>
					<input type="number" size="3" value="2" min="1" max="10" name="answer_range">
					<input type="number" size="3" value="5" min="1" max="10" name="answer_range">
				</li>
				<li>
					<p><?php _e( 'Course price', 'learnpress' ); ?></p>
					<input type="number" size="3" value="" min="0" name="price">
				</li>
			</ul>
		</fieldset>
	</form>
	<div class="lp-install-sample-message"></div>
	<p class="lp-install-sample__buttons">
		<button class="lp-button button button-primary lp-btn-install-sample-handle"
				data-action="lp_install_sample_data"
				data-message="<?php echo esc_attr( $message_install ); ?>"
				href="#">
			<?php esc_html_e( 'Install', 'learnpress' ); ?>
		</button>
		<a href="#"
		   class="lp-install-sample__toggle-options"
		   data-hide-text="<?php esc_attr_e( 'Hide options', 'learnpress' ); ?>"
		   data-show-text="<?php esc_attr_e( 'Show options', 'learnpress' ); ?>"
		>
			<?php esc_html_e( 'Show options', 'learnpress' ); ?>
		</a>
		<button class="lp-button button lp-btn-install-sample-handle"
				data-action="lp_uninstall_sample_data"
				data-message="<?php echo esc_attr( $message_uninstall ); ?>"
				href="#">
			<?php esc_html_e( 'Delete sample course', 'learnpress' ); ?>
		</button>
	</p>
</div>
