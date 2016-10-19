<?php
/**
 * Template for displaying question's explanation
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !$explanation ) {
	return;
}
?>
<li class="learn-press-question-explanation">
	<strong class="explanation-title"><?php esc_html_e('Explanation:', 'learnpress');?></strong>
	<?php echo do_shortcode( $explanation ); ?>
</li>