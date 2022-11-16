<?php
/**
 * Template for display error wrong name plugin learnpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['display'] ) || ! $data['display'] ) {
	return;
}
?>

<div class="lp-admin-notice lp-mes-beta-version">
	<p>
		The LearnPress plugin has new the beta version 4.2.0-beta.
	</p>
	<p>
		<?php
		printf(
			'Please download and install it from <a href="%s" target="_blank">here</a>',
			'https://downloads.wordpress.org/plugin/learnpress.4.2.0.zip'
		);
		?>
	</p>
	<p>You should install beta version on test site(staging, dev...). If have any errors, please send us to ...</p>
	<p><a href="#" target="_blank" rel="noopener">View changelog</a></p>
	<button type="button" class="notice-dismiss btn-lp-notice-dismiss" data="lp-beta-version">
		<span class="screen-reader-text">Dismiss this notice.</span>
	</button>
</div>
