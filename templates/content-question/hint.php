<?php
/**
 * Template for displaying question's hint
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $hint = $question->get_hint() ) {
	return;
}
?>
<div class="question-hint-content">
    <strong class="hint-title"><?php esc_html_e( 'Hint:', 'learnpress' ); ?></strong>
	<?php echo $hint; ?>
</div>