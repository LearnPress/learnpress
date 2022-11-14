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

<div id="lp-mes-beta-version">
	<p>
		The LearnPress plugin has new the beta version.
	</p>
	<p>
		<?php
		printf(
			'Please download and install it from <a href="%s" target="_blank">here</a>',
			'https://downloads.wordpress.org/plugin/learnpress.4.2.0.zip'
		);
		?>
	</p>
	<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
</div>
