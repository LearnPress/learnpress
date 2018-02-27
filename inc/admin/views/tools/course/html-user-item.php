<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-user-item" class="card">
    <h2><?php _e( 'Reset item data for an user', 'learnpress' ); ?></h2>
    <p class="description">
		<?php _e( 'Search results only show users have course data.', 'learnpress' ); ?>
    </p>
    <table>
        <tr>
            <td><?php _e( 'User ID or Email', 'learnpress' ); ?></td>
            <td><?php _e( 'Item ID', 'learnpress' ); ?></td>
        </tr>
        <tr>
            <td><input type="text" v-model="user_id"></td>
            <td><input type="text" v-model="item_id"></td>
        </tr>
    </table>
    <p v-if="message">{{message}}</p>
    <button class="button" @click="reset($event)"
            :disabled="resetting || !isValid()"><?php _e( 'Reset', 'learnpress' ); ?></button>
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
                                item_id: this.item_id
                            },
                            success: function (response) {
                                that.resetting = false;
                                that.message = response;
                            }
                        })
                    },
                    isValid: function () {
                        return this.user_id && this.item_id;
                    }
                }
            });
    });

</script>