<?php
/**
 * Template for displaying hint of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/hint.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $hint = $question->get_hint() ) {
	return;
}
?>

<div class="question-hint-content">
    <strong class="hint-title"><?php esc_html_e( 'Hint:', 'learnpress' ); ?></strong>
	<?php echo $hint; ?>
</div>