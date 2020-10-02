<?php
/**
 * Template for displaying main user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

get_header( 'profile' );

$profile = LP_Global::profile();
?>

<div id="learn-press-profile" <?php $profile->main_class(); ?>>
	<?php if ( $profile->is_public() || $profile->get_user()->is_guest() ) : ?>
		<div class="wrapper-profile-header wrap-fullwidth">
			<?php do_action( 'learn-press/before-user-profile', $profile ); ?>
		</div>

		<div class="lp-content-area">
			<?php
			if ( ! is_user_logged_in() ) {
				learn_press_print_messages( true );  // Print error in login & register.
			}

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/user-profile', $profile );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/after-user-profile', $profile );

			?>
		</div>
	<?php else : ?>
		<?php esc_html_e( 'This user does not public their profile.', 'learnpress' ); ?>
	<?php endif; ?>
</div>

<?php
get_footer( 'profile' );
