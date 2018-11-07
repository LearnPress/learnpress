<h2><?php _e( 'Welcome to LearnPress!', 'learnpress' ); ?></h2>
<p><?php _e( 'Thank you for choosing LearnPress to sell your courses online!', 'learnpress' ); ?></p>
<p><?php printf( __( 'In version <strong>%s</strong> of LearnPress we have a big update and need to upgrade your database to ensure system works properly.', 'learnpress' ), learn_press_get_current_version() ); ?></p>
<p><?php _e( 'We are very careful in upgrading the database but be sure to backup your database before upgrading to avoid possible risks.', 'learnpress' ); ?></p>
<p><?php _e( 'Click <strong>Yes, upgrade!</strong> button to start.', 'learnpress' ); ?></p>
<p class="lp-update-actions">
	<a href="<?php echo esc_url( admin_url( '' ) ); ?>" class="button"><?php _e( 'No, back to Admin', 'learnpress' ); ?></a>
	<a id="learn-press-update-button" class="button-primary button"><?php _e( 'Yes, upgrade!', 'learnpress' ); ?></a>
</p>
<input type="hidden" name="action" value="upgrade" />