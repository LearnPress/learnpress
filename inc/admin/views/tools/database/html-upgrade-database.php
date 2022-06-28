<?php
/**
 * Template tool upgrade database.
 *
 * @template html-upgrade-database
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die();

$user_agree_terms         = (int) LP_Settings::instance()->get( 'agree_terms', 0 );
$check_lp_need_upgrade_db = LP_Updater::instance()->check_lp_db_need_upgrade();

if ( ! $check_lp_need_upgrade_db ) {
	return;
}
?>

<div class="card" id="lp-tool-upgrade-db">
	<h2><?php echo sprintf( '%s', __( 'Upgrade Database.', 'learnpress' ) ); ?></h2>
	<p class="tools-button">
		<a class="button lp-btn-upgrade-db" href="javascript:;">
			<?php esc_html_e( 'Upgrade now', 'learnpress' ); ?>
		</a>
		<span class="spinner"></span>
		<?php wp_nonce_field( 'lp-nonce', 'lp-nonce' ); ?>
	</p>
	<div style="max-height: 500px; overflow: auto" class="wrapper-lp-status-upgrade"></div>

	<?php
	if ( ! $user_agree_terms ) {
		?>
		<div class="wrapper-terms-upgrade">
			<div class="terms-upgrade">
				<h2>Terms of Service update</h2>
				<div class="pd-2em">
					<p>To system Learnpress runs normally on v4.0.0 we need to upgrade database on your site.</p>
					<p>
						Please read these Terms and Conditions carefully before you upgrade the database of Learn Press
						operated by ThimPress on your website. Your access to and use this action is conditioned on your
						acceptance of and compliance with these Terms. These Terms apply to all users who use this
						action. By using this action, you agree to be bound by these Terms. If you disagree with any
						part of the terms then you may not use this option, and you should downgrade
					</p>
					<p>
						1. We do not take responsibility for the consequence with the data not from our
						plugins of this action. <strong style="color: #f55252"><i>Make sure you back up all your website before doing
								this</i></strong>.
					</p>
					<p>
						2. We do not take responsibility for the consequence of this action with your website
						if you stop this action before it has been finished successfully.
					</p>
					<p>
						3. All the results from the disconnection from your server or your website are not in
						our scope. So please make sure your connection is stable before you upgrade the
						database.
					</p>
					<p>
						<input type="checkbox" name="lp-agree-term">
						<span>
					<?php esc_html_e( 'I agree the new Terms of Service.', 'learnpress' ); ?>
					</span>
					</p>
					<p class="error"><?php esc_html_e( 'Please agree terms before upgrade!', 'learnpress' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	<div class="wrapper-lp-upgrade-message">
		<div class="lp-upgrade-message">
			<h2>Upgrade finished</h2>
			<div class="pd-2em">
				<div class="learn-press-message">
					<p>You have upgraded the database from plugin LearnPress successfully.</p>
					<p>You can use the LearnPress functions and LearnPress add-ons now.</p>
					<p>Read more document <a href="https://docspress.thimpress.com/admin-learnpress-4-0/">LearnPress
							version 4.0.0</a></p>
				</div>
			</div>
		</div>
	</div>
	<div class="wrapper-lp-loading">
		<?php lp_skeleton_animation_html( 7 ); ?>
	</div>
	<input type="hidden" name="message-when-upgrading"
		   value="<?php esc_html_e( 'Please don\'t close this tab until the completed upgrade', 'learnpress' ); ?>"/>
</div>
