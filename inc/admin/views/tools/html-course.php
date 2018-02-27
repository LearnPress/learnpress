<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div id="learn-press-reset-course" class="card">
    <h2><?php _e( 'Reset course', 'learnpress' ); ?></h2>

    <p>
        <input type="text" v-model="s">
        <button @click="search($event)"><?php _e('Search', 'learnpress');?></button>
    </p>

    <ul>
        <li v-for="course in courses">
            {{course.title}}
        </li>
    </ul>
</div>

<script>
    jQuery(function($){
        new Vue({
            el: '#learn-press-reset-course',
            data: {
                s: '',
                courses: [{
                    title: 'Sample',
                    id: 1
                }]
            },
            methods: {
                search: function (e) {
                    e.preventDefault();
                    if(!this.s){
                        return;
                    }

                    $.ajax({
                        url: 'admin-ajax.php',
                        data:{
                            action: 'learn-press-rs-search-course',
                            s: this.s
                        },
                        success:function (response) {

                        }
                    })
                }
            }
        });
    });

</script>