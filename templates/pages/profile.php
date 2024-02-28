<?php
/**
 * Template for displaying main user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $profile ) ) {
	return;
}
?>
	<div id="learn-press-profile" <?php $profile->main_class(); ?>>
		<div class="lp-content-area">
			<?php if ( $profile->is_public() ) : ?>
				<?php //do_action( 'learn-press/before-user-profile', $profile ); ?>
				<?php
				if ( ! is_user_logged_in() ) {
					learn_press_print_messages( true );
				}
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/user-profile', $profile );
				?>
			<?php else : ?>
				<?php
				$customer_message = [
					'status'  => 'error',
					'content' => __( 'This profile is private. Only the owner of this profile can view it.', 'learnpress' ),
				];
				Template::instance()->get_frontend_template( 'global/lp-message.php', compact( 'customer_message' ) );
				?>
			<?php endif; ?>
		</div>
	</div>
<?php
