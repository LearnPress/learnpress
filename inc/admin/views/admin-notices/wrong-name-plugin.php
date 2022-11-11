<?php
/**
 * Template for display error wrong name plugin learnpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) ) {
	return;
}
?>

<div class="notice <?php echo esc_attr( $data['class'] ?? '' ); ?>">
	<p>
		<?php
		printf(
			__(
				'The LearnPress plugin base directory must be <strong>learnpress/learnpres.php</strong> (case-sensitive) to ensure all functions work properly and are fully operational (currently <strong>%s</strong>)',
				'learnpress'
			),
			LP_PLUGIN_BASENAME
		);
		?>
	</p>
</div>
