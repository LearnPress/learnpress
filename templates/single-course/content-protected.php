<?php
/**
 * Template for displaying message for content protected
 *
 * @author  ThimPress
 * @version 1.1
 */
?>
<div class="learn-press-content-protected-message">
	<span class="icon"></span>
	<?php
		echo apply_filters(
			'learn_press_content_item_protected_message',
			sprintf( __( 'This content is protected, please <a href="%s">login</a> and enroll course to view this content', 'learnpress' ), learn_press_get_login_url( learn_press_get_current_url() ) ) ,
			$item
		); ?>
</div>