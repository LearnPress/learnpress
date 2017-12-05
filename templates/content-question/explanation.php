<?php
/**
 * Template for displaying explanation of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/explanation.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $explanation = $question->get_explanation() ) {
	return;
}
?>

<div class="question-explanation-content">
    <strong class="explanation-title"><?php esc_html_e( 'Explanation:', 'learnpress' ); ?></strong>
	<?php echo $explanation; ?>
</div>