<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-user-item" class="card">
	<h2><?php _e( 'Reset Item Progress', 'learnpress' ); ?></h2>
	<div class="description">
		<?php _e( 'This action will reset progress of a specific lesson or quiz.', 'learnpress' ); ?>
	</div>
	<div class="content">
		<p>
			<input type="text" v-model="user_id" @keyup="update($event)" placeholder="<?php _e( 'User ID or Email', 'learnpress' ); ?>">
		</p>
		<p>
			<input type="text" v-model="item_id" @keyup="update($event)" placeholder="<?php _e( 'ID of quiz or lesson', 'learnpress' ); ?>">
		</p>
	</div>
	<p v-if="message">{{message}}</p>
	<button class="button" @click="reset($event)"
			:disabled="resetting || !isValid()"><?php _e( 'Reset', 'learnpress' ); ?></button>
</div>

<?php

// Translation
$localize = array(
	'reset_course_users' => __( 'Are you sure to reset progress of this item?', 'learnpress' ),
);
?>
<script>
	window.$Vue = window.$Vue || Vue;

	jQuery(function ($) {
		var js_localize = <?php echo wp_json_encode( $localize ); ?>

			new $Vue({
				el: '#learn-press-reset-user-item',
				data: {
					user_id: '',
					item_id: '',
					resetting: false,
					message: ''
				},
				watch: {},
				created: function () {

				},
				mounted: function () {
					$(this.$el).closest('form').on('submit', function () {
						return false;
					})
				},
				methods: {
					reset: function (e) {
						e.preventDefault();
						if (!this.user_id || !this.item_id) {
							return;
						}
						if (!confirm(js_localize.reset_course_users)) {
							return;
						}
						var that = this;
						this.resetting = true;
						this.message = '';
						$.ajax({
							url: '',
							data: {
								'lp-ajax': 'rs-reset-user-item',
								user_id: this.user_id,
								item_id: this.item_id,
								nonce: lpGlobalSettings.nonce
							},
							success: function (response) {
								that.resetting = false;
								that.message = response;
							}
						})
					},
					update: function (e) {
						e.preventDefault();
					},
					isValid: function () {
						return this.user_id && this.item_id;
					}
				}
			});
	});

</script>
