<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-course-users" class="card">
    <h2><?php _e( 'Reset course progress', 'learnpress' ); ?></h2>
    <div class="description">
        <p><?php _e( 'This action will reset progress of a course for all users have enrolled.', 'learnpress' ); ?></p>
        <p><?php _e( 'Search results only show course have user data.', 'learnpress' ); ?></p>
    </div>
    <p>
        <input type="text" name="s" @keyup="updateSearch($event)" autocomplete="off"
               placeholder="<?php esc_attr_e( 'Search course by name', 'learnpress' ); ?>">
        <button class="button" @click="search($event)"
                :disabled="s.length < 3"><?php _e( 'Search', 'learnpress' ); ?></button>
    </p>

    <template v-if="courses.length > 0">
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th width="50"><?php _e( 'ID', 'learnpress' ); ?></th>
                <th><?php _e( 'Name', 'learnpress' ); ?></th>
                <th width="80"><?php _e( 'Students', 'learnpress' ); ?></th>
                <th width="80"><?php _e( 'Actions', 'learnpress' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="course in courses">
                <td>#{{course.id}}</td>
                <td>{{course.title}}</td>
                <td>{{course.students}}</td>
                <td>
                    <a class="action-reset dashicons"
                       href=""
                       @click="reset($event, course);"
                       :class="resetActionClass(course)"></a>
<!--                    <span v-else-if="course.status=='done'">--><?php //_e( 'Done', 'learnpress' ); ?><!--</span>-->
<!--                    <span v-else-if="course.status=='resetting'">--><?php //_e( 'Resetting...', 'learnpress' ); ?><!--</span>-->
                </td>
            </tr>
            </tbody>
        </table>
    </template>
    <template v-else>
        <p v-if="s.length < 3"><?php _e( 'Please enter at least 3 characters to searching courses.', 'learnpress' ); ?></p>
        <p v-else-if="status=='result'"><?php _e( 'No course found.', 'learnpress' ); ?></p>
        <p v-else-if="status=='searching'"><?php _e( 'Searching course...', 'learnpress' ); ?></p>
    </template>
</div>

<?php

// Translation
$localize = array(
	'reset_course_users' => __( 'Are you sure to reset course progress of all users enrolled this course?', 'learnpress' )
);
?>
<script>
    jQuery(function ($) {
        var js_localize = <?php echo wp_json_encode( $localize );?>

            new Vue({
                el: '#learn-press-reset-course-users',
                data: {
                    s: '',
                    status: false,
                    courses: []
                },
                methods: {
                    resetActionClass: function (course) {
                        return {
                            'dashicons-trash': !course.status,
                            'dashicons-yes': course.status === 'done',
                            'dashicons-update': course.status === 'resetting'
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
                                'lp-ajax': 'rs-search-courses',
                                s: this.s
                            },
                            success: function (response) {
                                that.courses = LP.parseJSON(response);
                                that.status = 'result';
                            }
                        })
                    },

                    reset: function (e, course) {
                        e.preventDefault();

                        if (!confirm(js_localize.reset_course_users)) {
                            return;
                        }
                        var that = this;
                        course.status = 'resetting';
                        $.ajax({
                            url: '',
                            data: {
                                'lp-ajax': 'rs-reset-course-users',
                                id: course.id
                            },
                            success: function (response) {
                                response = LP.parseJSON(response);
                                if (response.id == course.id) {
                                    for (var i = 0, n = that.courses.length; i < n; i++) {
                                        if (that.courses[i].id === course.id) {
                                            that.courses[i].status = 'done';
                                            break;
                                        }
                                    }
                                }
                            }
                        })
                    }
                }
            });
    });

</script>