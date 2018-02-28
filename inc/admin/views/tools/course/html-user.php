<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-user-courses" class="card">
    <h2><?php _e( 'Reset course data by user', 'learnpress' ); ?></h2>
    <p class="description">
		<?php _e( 'Search results only show users have course data.', 'learnpress' ); ?>
    </p>
    <p>
        <input class="wide-fat" type="text" name="s" @keyup="updateSearch($event)">
        <button class="button" @click="search($event)" :disabled="s.length < 3"><?php _e( 'Search', 'learnpress' ); ?></button>
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
                               @click="reset($event, user, course.id);"><?php _e( 'Reset', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </td>
                <td>
                    <a v-if="!user.status" href=""
                       @click="reset($event, user);"><?php _e( 'Reset all', 'learnpress' ); ?></a>
                    <span v-else-if="user.status=='done'"><?php _e( 'Done', 'learnpress' ); ?></span>
                    <span v-else-if="user.status=='resetting'"><?php _e( 'Resetting...', 'learnpress' ); ?></span>
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
	'reset_course_users' => __( 'Remove all users from this course?', 'learnpress' )
);
?>
<script>
    jQuery(function ($) {
        var js_localize = <?php echo wp_json_encode( $localize );?>

            new Vue({
                el: '#learn-press-reset-user-courses',
                data: {
                    s: '',
                    status: false,
                    users: []
                },
                methods: {
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
                                that.users = LP.parseJSON(response);
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
                        user.status = 'resetting';
                        $.ajax({
                            url: '',
                            data: {
                                'lp-ajax': 'rs-reset-user-courses',
                                user_id: user.id,
                                course_id: course_id
                            },
                            success: function (response) {
                                response = LP.parseJSON(response);
                                //if (response.id == user.id) {
                                for (var i = 0, n = that.users.length; i < n; i++) {
                                    if (that.users[i].id === user.id) {
                                        that.users[i].status = 'done';
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