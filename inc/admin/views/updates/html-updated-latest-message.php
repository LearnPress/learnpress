<?php
/**
 * Template for displaying message after LP updated to latest version
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.8
 */

defined( 'ABSPATH' ) || exit;

//LearnPress::instance()->session->remove( 'do-update-learnpress', true );
?>

<div class="updated notice">
	<p><?php esc_html_e( 'LearnPress has just updated to the latest version.', 'learnpress' ); ?></p>
</div>
