<?php
/**
 * Template for displaying course curriculum in popup
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.4.9
 */


defined('ABSPATH') || exit;
?>

<div id="popup-right-sidebar" class="popup-right-sidebar is-collapsed">
	<span class="popup-right-sidebar__toggle lp-icon-sidebar-left"></span>
	<ul class="popup-right-sidebar__items">
		<li class="popup-right-sidebar__item item-notes active">
			<span class="popup-right-sidebar__icon lp-icon-notes"></span>
			<span class="popup-right-sidebar__text"><?php esc_html_e('Notes', 'learnpress'); ?></span>
		</li>

		<li class="popup-right-sidebar__item item-chat-room">
			<span class="popup-right-sidebar__icon lp-icon-chat"></span>
			<span class="popup-right-sidebar__text"><?php esc_html_e('Chat Room', 'learnpress'); ?></span>
		</li>

		<li class="popup-right-sidebar__item item-ai-assistant">
			<span class="popup-right-sidebar__icon lp-icon-ai-assistant"></span>
			<span class="popup-right-sidebar__text"><?php esc_html_e('AI Assistant', 'learnpress'); ?></span>
		</li>

		<li class="popup-right-sidebar__item item-dark-mode">
			<span class="popup-right-sidebar__icon lp-icon-light"></span>
			<span class="popup-right-sidebar__text"><?php esc_html_e('Light Mode', 'learnpress'); ?></span>
		</li>
	</ul>
</div>