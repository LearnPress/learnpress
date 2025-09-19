<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.2.5.6
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-user-progress" class="card">
	<h2><?php esc_html_e( 'Reset User Progress', 'learnpress' ); ?></h2>
	<div class="description">
		<p><?php esc_html_e( 'This action will reset all course progresses of selected users.', 'learnpress' ); ?></p>
		<p><?php esc_html_e( 'Search and select multiple users whose progress you want to reset.', 'learnpress' ); ?></p>
	</div>
	<div class="content">
		<form id="lp-reset-user-progress-form" method="post" action="">
			<div class="lp-user-selection-section">
				<div class="lp-autocomplete-container">
					<input
						type="text"
						id="lp-user-autocomplete"
						name="user_search"
						class="widefat lp-autocomplete-input"
						placeholder="<?php esc_attr_e( 'Type to search and select users...', 'learnpress' ); ?>"
						autocomplete="off"
					/>
					<div id="lp-autocomplete-dropdown" class="lp-autocomplete-dropdown" style="display: none;">
						<div class="lp-autocomplete-results"></div>
					</div>
				</div>
				<p class="description"><?php esc_html_e( 'Type to search for users. Click on a user to select them.', 'learnpress' ); ?></p>
				<div id="lp-selected-users-list" class="lp-selected-users-list">
					<p class="no-users"><?php esc_html_e( 'No users selected yet.', 'learnpress' ); ?></p>
				</div>
			</div>

			<div>
				<button class="button button-primary lp-button-reset-user-progress" type="submit">
					<?php esc_html_e( 'Reset Progress', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</div>
		</form>
	</div>
</div>

<style>
.lp-autocomplete-container {
	position: relative;
}

.lp-autocomplete-dropdown {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: white;
	border: 1px solid #ddd;
	border-top: none;
	border-radius: 0 0 4px 4px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	z-index: 1000;
	max-height: 200px;
	overflow-y: auto;
}

.lp-autocomplete-results {
	padding: 0;
}

.lp-autocomplete-item {
	padding: 8px 12px;
	cursor: pointer;
	border-bottom: 1px solid #eee;
	transition: background-color 0.2s;
}

.lp-autocomplete-item:hover {
	background-color: #f5f5f5;
}

.lp-autocomplete-item:last-child {
	border-bottom: none;
}

.lp-autocomplete-item .user-name {
	font-weight: bold;
	color: #333;
}

.lp-autocomplete-item .user-id {
	font-size: 12px;
	color: #999;
	font-style: italic;
}

.lp-autocomplete-item .user-email {
	font-size: 12px;
	color: #666;
	margin-left: 5px;
}

.lp-autocomplete-item.selected {
	background-color: #e3f2fd;
	color: #1976d2;
}

.lp-autocomplete-item.selected .user-name {
	color: #1976d2;
}

.selected-indicator {
	float: right;
	color: #4caf50;
	font-weight: bold;
}

.lp-selected-users-list {
	min-height: 50px;
	margin-top: 10px;
}

.lp-selected-users-list .no-users {
	color: #666;
	font-style: italic;
	margin: 0;
}

.lp-selected-user-item {
	display: inline-block;
	background: #0073aa;
	color: white;
	padding: 5px 10px;
	margin: 5px 5px 0 0;
	border-radius: 3px;
	font-size: 12px;
}

.lp-selected-user-item .remove-user {
	margin-left: 8px;
	cursor: pointer;
	color: #ff6b6b;
	font-weight: bold;
}

.lp-selected-user-item .remove-user:hover {
	color: #ff5252;
}
</style>
