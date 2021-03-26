<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || die();
?>

<div class="card">
	<h2><?php _e( 'Remove current Data', 'learnpress' ); ?></h2>
	<p><?php _e( 'Remove all courses, lessons, quizzes and questions.', 'learnpress' ); ?></p>
	<form method="post" name="learn-press-form-remove-data">
		<div class="">
			<p><?php _e( 'Be careful before using this action!', 'learnpress' ); ?></p>
		</div>
		<label class="hide-if-js">
			<input type="checkbox" name="action" value="learn-press-remove-data"/>
			<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
		</label>
		<p class="tools-button">
			<button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
		</p>
		<?php wp_nonce_field( 'learn-press-remove-data', 'remove-data-nonce' ); ?>
	</form>
</div>
