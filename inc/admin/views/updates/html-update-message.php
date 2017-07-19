<div class="learn-press-message notice notice-warning">
    <p>
		<?php _e( '<strong>LearnPress update</strong> – We need to update your database to the latest version.', 'learnpress' ); ?>
    </p>
    <p>
        <a class="button button-primary"
           href="<?php echo esc_url( untrailingslashit( admin_url( 'admin.php?do-update-learnpress=yes&redirect=' . urlencode( learn_press_get_current_url() ) ) ) ); ?>"><?php _e( 'Update Now', 'learnpress' ); ?></a>
    </p>
</div>