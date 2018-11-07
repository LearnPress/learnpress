<h2><?php _e( 'Upgrade completed successfully!', 'learnpress' ); ?></h2>
<h3 style="font-size: 14px;"><?php _e( 'What\'s next?', 'learnpress' ); ?></h3>
<ul>
	<li>
		<a href="<?php echo admin_url( 'edit.php?post_type=lp_course' ); ?>"><?php _e( 'Manage courses', 'learnpress' ); ?></a>
	</li>
	<li>
		<a href="<?php echo admin_url( 'post-new.php?post_type=lp_course' ); ?>"><?php _e( 'Create a new course', 'learnpress' ); ?></a>
	</li>
	<li>
		<a href="<?php echo admin_url( 'options-general.php?page=learn-press-settings' ); ?>"><?php _e( 'Setting up your LearnPress', 'learnpress' ); ?></a>
	</li>
	<li>
		<a href="<?php echo admin_url( 'admin.php?page=learn-press-addons' ); ?>"><?php _e( 'Manage add-ons', 'learnpress' ); ?></a>
	</li>
	<li><a href="<?php echo admin_url( 'index.php' ); ?>"><?php _e( 'Dashboard', 'learnpress' ); ?></a></li>
</ul>