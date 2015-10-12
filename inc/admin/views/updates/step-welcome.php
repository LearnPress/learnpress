<h2><?php _e( 'Welcome to LearnPress!', 'learn_press' ); ?></h2>
<p><?php _e( 'Thank you for choosing LearnPress to selling your course online!', 'learn_press' ); ?></p>
<p><?php _e( 'In version 1.0 of LearnPress we have a big update and need to upgrade your database to ensure system works properly', 'learn_press' ); ?></p>
<p><?php _e( 'Click Let\'s Go! button to start', 'learn_press' ); ?></p>
<p class="lp-update-actions">
	<a href="<?php echo esc_url( admin_url( '' ) ); ?>" class="button"><?php _e( 'Not now', 'learn_press' ); ?></a>
	<a href="<?php echo esc_url( $this->next_link() ); ?>" class="button-primary button"><?php _e( 'Let\'s Go!', 'learn_press' ); ?></a>
</p>