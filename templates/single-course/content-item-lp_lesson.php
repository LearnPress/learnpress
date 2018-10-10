<?php
/**
 * Template for displaying lesson item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_lesson.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$item   = LP_Global::course_item();
$course = LP_Global::course();
?>

<div <?php learn_press_content_item_summary_class(); ?>>
    <div class="content-item-scrollable">
        <div class="content-item-wrap">
			<?php

			foreach ( $course->get_sections() as $section ) {
				foreach ( $section->get_items() as $itemx ) {
					?>
                    <div v-show="isShowItem(<?php echo $itemx->get_id(); ?>)">
                        <div class="content-item-summary"><?php echo $itemx->get_content(); ?></div>
                    </div>
					<?php
				}
			}
			?>
        </div>
    </div>
    <template v-if="loaded && 0">
        <div class="content-item-scrollable">
            <div class="content-item-wrap">
                <div class="content-item-summary">

                    <h3 class="course-item-title question-title">{{currentItem.name}}</h3>
                    <div class="content-item-description lesson-description" v-html="currentItem.content">
                    </div>
                    <!--                    <form method="post" name="learn-press-form-complete-lesson"-->
                    <!--                          data-confirm="Do you want to complete lesson &quot;Lesson 1&quot;?"-->
                    <!--                          class="learn-press-form form-button"><input type="hidden" name="id" value="10135"> <input-->
                    <!--                                type="hidden" name="course_id" value="10134"> <input type="hidden"-->
                    <!--                                                                                     name="complete-lesson-nonce"-->
                    <!--                                                                                     value="9a355e25cb"> <input-->
                    <!--                                type="hidden" name="type" value="lp_lesson"> <input type="hidden" name="lp-ajax"-->
                    <!--                                                                                    value="complete-lesson"> <input-->
                    <!--                                type="hidden" name="noajax" value="yes">-->
                    <!--                        <button class="lp-button button button-complete-item button-complete-lesson">Complete-->
                    <!--                        </button>-->
                    <!--                    </form>-->
                </div>
                <!--                <div class="course-item-nav">-->
                <!--                    <div class="next"><span>Next</span> <a-->
                <!--                                href="http://localhost/learnpress/dev/courses/course-no-69/lessonses/lesson-2-3/">-->
                <!--                            Lesson 2 </a></div>-->
                <!--                </div>-->
            </div>
        </div>
    </template>
    <template v-else="loaded && 0">
		<?php

		//do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

		//do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

		//do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );

		?>
    </template>

    <button type="button" @click="_completeItem" :disabled="currentItem.completed">
        <template v-if="currentItem.completed">{{'<?php esc_html_e( 'Completed', 'learnpress' ); ?>'}}</template>
        <template v-else>{{'<?php esc_html_e( 'Complete', 'learnpress' ); ?>'}}</template>
    </button>
</div>

<script>
    (function ($) {
        $(document).on('course-ready', function () {
            new Vue({
                el: '#learn-press-content-item',
                data: function () {
                    return {
                        loaded: false
                    }
                },
                computed: {
                    currentItem: function () {
                        return this.$courseStore().currentItem;
                    }
                },
                mounted: function () {
                    this.loaded = true;
                },
                methods: {
                    isShowItem: function (itemId) {
                        return this.currentItem.id == itemId;
                    },
                    _completeItem: function () {
                        this.currentItem.completed = true;
                    },
                    $courseStore: function (prop, value) {
                        var $store = window.$courseStore;
                        if (prop) {
                            if (arguments.length == 2) {
                                $store.getters['all'][prop] = value;
                            } else {
                                return $store.getters['all'][prop]
                            }
                        }

                        return $store.getters['all'];
                    }
                }
            });
        })
    })(jQuery);
</script>
