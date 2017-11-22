<div class="card">
    <h2><?php _e( 'Upgrade Database', 'learnpress' ); ?></h2>
    <p><?php _e( 'Force to upgrade database', 'learnpress' ); ?></p>
    <p class="tools-button">
        <a class="button" href="<?php echo admin_url( 'index.php?page=lp-database-updater' ); ?>"><?php esc_html_e( 'Upgrade', 'learnpress' ); ?></a>
    </p>
</div>
<!--
<div class="card">
	<h2><?php _e( 'Upgrade Courses', 'learnpress' ); ?></h2>
	<p><?php _e( 'Upgrade courses, lessons, quizzes and questions from version less than 1.0.', 'learnpress' ); ?></p>
	<div class="learn-press-message">
		<p><?php _e( 'Use this action to force system to upgrade outdated data to latest version.', 'learnpress' ); ?></p>
	</div>
	<div class="learn-press-message lp-error">
		<p><?php _e( 'All courses will be upgraded whether you have done this action in the past. So please remove all courses before you upgrade to prevent duplicated courses.', 'learnpress' ); ?></p>
	</div>
	<p class="tools-button">
		<a class="button" href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=learn_press_upgrade_from_09&force=true' ), 'learn-press-upgrade-09' ); ?>"><?php esc_html_e( 'Upgrade', 'learnpress' ); ?></a>
	</p>
</div>-->
<div class="card">
	<h2><?php _e( 'Remove current Data', 'learnpress' ); ?></h2>
	<p><?php _e( 'Remove all courses, lessons, quizzes and questions', 'learnpress' ); ?></p>
	<form method="post" name="learn-press-form-remove-data">
		<div class="learn-press-message lp-error">
			<p><?php _e( 'Be careful before using this action!', 'learnpress' ); ?></p>
		</div>
		<label class="hide-if-js">
			<input type="checkbox" name="action" value="learn-press-remove-data" />
			<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
		</label>
		<p class="tools-button">
			<button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
		</p>
		<?php wp_nonce_field( 'learn-press-remove-data', 'remove-data-nonce' ); ?>
	</form>
</div>
<div class="card">
	<h2><?php _e( 'Remove outdated Data', 'learnpress' ); ?></h2>
	<p><?php _e( 'Remove all courses, lessons, quizzes and questions from version less than 1.0.', 'learnpress' ); ?></p>
	<form method="post" name="learn-press-form-remove-data">
		<div class="learn-press-message lp-error">
			<p><?php _e( 'Be careful before using this action! Only use this action in case all outdated data has been upgraded.', 'learnpress' ); ?></p>
		</div>
		<label class="hide-if-js">
			<input type="checkbox" name="action" value="learn-press-remove-old-data" />
			<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
		</label>
		<p class="tools-button">
			<button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
		</p>
		<?php wp_nonce_field( 'learn-press-remove-old-data', 'remove-old-data-nonce' ); ?>

	</form>
</div>