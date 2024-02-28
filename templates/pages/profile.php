<?php
/**
 * Template for displaying main user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $profile ) ) {
	return;
}
?>
	<div id="learn-press-profile" <?php $profile->main_class(); ?>>
		<div class="lp-content-area">
			<?php do_action( 'learn-press/user-profile', $profile ); ?>
		</div>
	</div>
<?php
