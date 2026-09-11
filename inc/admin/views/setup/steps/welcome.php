<?php
/**
 * Template for displaying welcome step of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>
<div class="lp-setup-welcome">
	<div class="lp-setup-welcome__eyebrow">
		<span class="lp-setup-welcome__eyebrow-dot" aria-hidden="true"></span>
		<?php esc_html_e( 'Let’s setup your LMS', 'learnpress' ); ?>
	</div>

	<h2><?php esc_html_e( 'Welcome to LearnPress', 'learnpress' ); ?></h2>

	<p><?php esc_html_e( 'Create courses, manage students, and start selling online with LearnPress.', 'learnpress' ); ?></p>
</div>
