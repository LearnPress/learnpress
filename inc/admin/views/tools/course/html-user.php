<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-user-courses" class="card">
	<h2><?php _e( 'Reset user progress', 'learnpress' ); ?></h2>
	<div class="description">
		<p><?php _e( 'This action will reset progress of all courses that an user has enrolled.', 'learnpress' ); ?></p>
		<p><?php _e( 'Search results only show users have course data.', 'learnpress' ); ?></p>
	</div>
	<p>
		<input class="wide-fat" type="text" name="s" @keyup="updateSearch($event)"
			   placeholder="<?php esc_attr_e( 'Search user by login name or email', 'learnpress' ); ?>">
		<button class="button" @click="search($event)"
				:disabled="s.length < 3"><?php _e( 'Search', 'learnpress' ); ?></button>
	</p>

	<template v-if="users.length > 0">
		<table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>
				<th width="50"><?php _e( 'ID', 'learnpress' ); ?></th>
				<th width="200"><?php _e( 'Name', 'learnpress' ); ?></th>
				<th><?php _e( 'Courses', 'learnpress' ); ?></th>
				<th width="80"><?php _e( 'Actions', 'learnpress' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr v-for="user in users">
				<td>#{{user.id}}</td>
				<td>{{user.username}} ({{user.email}})</td>
				<td>
					<ul class="courses-list">
						<li v-for="course in user.courses">
							<a :href="course.url" target="_blank">{{course.title}} (#{{course.id}})</a>
							<a href=""
							   class="action-reset dashicons"
							   data-reset="single"
							   @click="reset($event, user, course.id);"
							   :class="resetActionClass(user, course)"></a>
						</li>
					</ul>
				</td>
				<td>
					<a href=""
					   class="action-reset dashicons"
					   data-reset="all"
					   :class="resetActionClass(user)"
					   @click="reset($event, user);"></a>
					<span style="font-size: 12px"><?php echo esc_html__( 'Delete All', 'learnpress' ); ?></span>
				</td>
			</tr>
			</tbody>
		</table>
	</template>
	<template v-else>
		<p v-if="s.length < 3"><?php _e( 'Please enter at least 3 characters to searching users.', 'learnpress' ); ?></p>
		<p v-else-if="status=='result'"><?php _e( 'No user found.', 'learnpress' ); ?></p>
		<p v-else-if="status=='searching'"><?php _e( 'Searching user...', 'learnpress' ); ?></p>
	</template>
</div>

<?php

// Translation
$localize = array(
	'reset_course_users' => __( 'Are you sure to reset course progress of all users enrolled this course?', 'learnpress' ),
);
?>
<script>
	window.$Vue = window.$Vue || Vue;

	jQuery(function ($) {
		var js_localize = <?php echo wp_json_encode( $localize ); ?>

			new $Vue({
				el: '#learn-press-reset-user-courses',
				data: {
					s: '',
					status: false,
					users: []
				},
				methods: {
					resetActionClass: function (user, course) {
						var status = course ? course.status : user.status;
						return {
							'dashicons-trash': !status,
							'dashicons-yes': status === 'done',
							'dashicons-update': status === 'resetting'
						}
					},
					updateSearch: function (e) {
						this.s = e.target.value;
						this.status = false;
						e.preventDefault();
					},
					search: function (e) {
						e.preventDefault();

						var that = this;
						this.s = $(this.$el).find('input[name="s"]').val();

						if (this.s.length < 3) {
							return;
						}

						this.status = 'searching';
						this.courses = [];

						$.ajax({
							url: '',
							data: {
								'lp-ajax': 'rs-search-users',
								s: this.s
							},
							success: function (response) {
								var users = LP.parseJSON(response);
								for(var i = 0; i < users.length; i++) {
									for (var j in users[i].courses) {
										users[i].courses[j].status = ''
									}
								}
								that.users = users;
								that.status = 'result';
							}
						})
					},

					reset: function (e, user, course_id) {
						e.preventDefault();

						if (!confirm(js_localize.reset_course_users)) {
							return;
						}
						var that = this;
						if(course_id){
							user.courses[course_id].status = 'resetting'
						}else{
							for(var j in user.courses ){
								user.courses[j].status = 'resetting'
							}
							user.status = 'resetting';
						}

						var object_reset = $(e.target).data('reset');

						$.ajax({
							url: '',
							data: {
								'lp-ajax': 'rs-reset-user-courses',
								user_id: user.id,
								course_id: course_id,
								object_reset: object_reset
							},
							success: function (response) {
								response = LP.parseJSON(response);
								//if (response.id == user.id) {
								for (var i = 0, n = that.users.length; i < n; i++) {
									if (that.users[i].id === user.id) {
										if(course_id){
											that.users[i].courses[course_id].status = 'done'
										}else{
											for(var j in that.users[i].courses ){
												that.users[i].courses[j].status = 'done'
											}
											user.status = 'done';
										}
										break;
									}
								}
								// }
							}
						})
					}
				}
			});
	});

</script>
