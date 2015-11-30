<h2><?php _e( 'Upgrade complete successfully!', 'learn_press' ); ?></h2>
<h3 style="font-size: 14px;"><?php _e( 'What\'s next?', 'learn_press' );?></h3>
<ul>
	<li><a href="<?php echo admin_url( 'post-new.php?post_type=lp_course' );?>"><?php _e( 'Create a new course', 'learn_press' ); ?></a></li>
	<li><a href="<?php echo admin_url( 'options-general.php?page=learn_press_settings' );?>"><?php _e( 'Setting up your LearnPress', 'learn_press' ); ?></a></li>
	<li><a href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons' );?>"><?php _e( 'Manage add-ons', 'learn_press' ); ?></a></li>
	<li><a href="<?php echo admin_url( 'index.php' );?>"><?php _e( 'Dashboard', 'learn_press' ); ?></a></li>
</ul>