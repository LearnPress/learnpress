<?php
/**
 * Template for displaying finish step.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>
<h2><?php _e( 'Finish', 'learnpress' ); ?></h2>

<p><?php _e( 'LearnPress LMS is ready to go!', 'learnpress' ); ?></p>

<p class="finish-buttons">
	<a class="button"
		id="install-sample-course"
		href="<?php echo esc_url_raw( admin_url( 'admin.php?page=learn-press-tools' ) ); ?>">
		<?php _e( 'Install a demo course', 'learnpress' ); ?>
	</a>

	<a class="button" href="https://docspress.thimpress.com/admin-learnpress-4-0/">
		<?php _e( 'View Documentation', 'learnpress' ); ?>
	</a>

	<a class="button" href="<?php echo esc_url_raw( admin_url( 'post-new.php?post_type=lp_course' ) ); ?>">
		<?php _e( 'Create a new course', 'learnpress' ); ?>
	</a>

	<a class="button" href="<?php echo esc_url_raw( admin_url( 'index.php' ) ); ?>">
		<?php _e( 'Back to Dashboard', 'learnpress' ); ?>
	</a>
</p>
