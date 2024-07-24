<?php
/**
 * Template tool upgrade database.
 *
 * @template html-upgrade-database
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.1
 */

defined( 'ABSPATH' ) or die();

$user_agree_terms         = (int) LP_Settings::instance()->get( 'agree_terms', 0 );
$check_lp_need_upgrade_db = LP_Updater::instance()->check_lp_db_need_upgrade();

if ( ! $check_lp_need_upgrade_db ) {
	return;
}
?>

<div class="card" id="lp-tool-upgrade-db">
	<h2><?php echo sprintf( '%s', __( 'Upgrade Database', 'learnpress' ) ); ?></h2>
	<p class="tools-button">
		<a class="button lp-btn-upgrade-db" href="javascript:;">
			<?php esc_html_e( 'Upgrade now', 'learnpress' ); ?>
		</a>
		<span class="spinner"></span>
		<?php wp_nonce_field( 'lp-nonce', 'lp-nonce' ); ?>
	</p>
	<div style="max-height: 500px; overflow: auto" class="wrapper-lp-status-upgrade"></div>

	<?php
	if ( $user_agree_terms !== $check_lp_need_upgrade_db ) {
		?>
		<div class="wrapper-terms-upgrade">
			<div class="terms-upgrade">
				<h2>Terms of Service update</h2>
				<div class="pd-2em">
					<span>To system LearnPress runs normally, we need to upgrade database on your site.</span>
					<p>
						IMPORTANT: Before upgrading the LearnPress database on your website, please read these Terms of
						Services carefully.
					</p>
					<p>
						Our Terms of Services, provided by ThimPress, outline the precautions for upgrading the
						LearnPress database on your website. By using this feature, you agree to be bound by these
						Terms. If you disagree with any part, you should not upgrade the database and consider
						downgrading LearnPress versions if necessary.
					</p>
					<h4>ACCEPTANCE OF TERMS</h4>
					<span>
						1. We do not take responsibility for the loss data that is not from our LearnPress plugins. This
						means we cannot guarantee the safety of data unrelated to LearnPress during the database upgrade
						process . <strong style="color: #f55252"><i>It's essential to back up your entire website before
								upgradingspan</i></strong>.
					</span>
					<p>
						2. We are not responsible for the issues caused by interrupting the upgrade process.
						Interrupting
						the process before finishing can lead to problems on your website. Please ensure that the
						LearnPress database is upgraded successfully.
					</p>
					<p>
						3. A stable internet connection is essential for a successful upgrade since any Internet
						disconnection can lead to unexpected problems. We do not take responsibility for any issues
						caused by any disconnection.
					</p>
					<h4>ACCEPTANCE OF TERMS</h4>
					<span>
						1.Back up your website before upgrading. This will make sure you can restore your website in
						case any unexpected errors happen.
					</span>
					<p>
						2. Maintain a stable Internet connection throughout the process. This will minimize the risk of
						breaking down your website.
					</p>
					<p>
						3. Do not interrupt the process once it starts. You should let the process run for a successful
						database update.
					</p>
					<p>
						<input type="checkbox" name="lp-agree-term">
						<span>
					<?php esc_html_e( 'I agree the new Terms of Service.', 'learnpress' ); ?>
					</span>
					</p>
					<p class="error"><?php esc_html_e( 'Please agree to the terms before upgrade!', 'learnpress' ); ?></p>
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
					<p>Read more document <a href="https://docs.thimpress.com/learnpress/">LearnPress</a></p>
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
