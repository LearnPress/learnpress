<?php
/**
 * Template for displaying question's explanation
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $explanation = $question->get_explanation() ) {
	return;
}
?>
<div class="question-explanation">
    <strong class="explanation-title"><?php esc_html_e( 'Explanation:', 'learnpress' ); ?></strong>
	<?php echo $explanation; ?>
</div>