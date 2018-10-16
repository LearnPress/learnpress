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
 *
 * @var LP_Course_Item $itemx
 */
defined( 'ABSPATH' ) || exit();

$item   = LP_Global::course_item();
$course = LP_Global::course();

?>

<div :class="mainClass()" data-classes="<?php echo join( ' ', learn_press_content_item_summary_main_classes() ); ?>">
    <!--    <div class="content-item-scrollable">-->
    <!--        <div class="content-item-wrap">-->
    [[{{currentItem.id}}, {{courseLoaded}}]]
	<?php
	foreach ( $course->get_sections() as $section ) {
		foreach ( $section->get_items() as $itemx ) {
			?>
            <div v-show="isShowItem(<?php echo $itemx->get_id(); ?>)">
				<?php echo $itemx->get_content(); ?>
            </div>
			<?php
		}
	}
	?>

    <button type="button" @click="_completeItem($event)" :disabled="currentItem.completed">
        <template v-if="currentItem.completed">{{'<?php esc_html_e( 'Completed', 'learnpress' ); ?>'}}</template>
        <template v-else>{{'<?php esc_html_e( 'Complete', 'learnpress' ); ?>'}}</template>
    </button>
</div>

<script>
    (function ($) {
        function xxx() {
            return new Vue({
                el: '#learn-press-content-item',
                data: function () {
                    return {
                        loaded: false,
                        courseLoaded: false,
                        currentItem: {}
                    }
                },
                computed: {
//                    currentItem: function () {
//                        console.log('currentItem')
//                        return this.$courseStore() ? this.$courseStore().currentItem : {};
//                    },
                    abcx: function () {
                        return this.abc();
                    }
                },
                watch: {
                    courseLoaded: function (newValue) {
                        this.currentItem = this.$courseStore('currentItem');

                        return newValue;
                    },
                    'currentItem.id': function (a, b) {
                        if (a != b) {
                            LP.setUrl(this.currentItem.permalink);
                            this.$('.content-item-scrollable').scrollTop(0);
                        }
                        return a;
                    }
                },
                mounted: function () {
                    var $vm = this;
                    //this.loaded = true;
                    $(document).on('LP.click-curriculum-item', function (e, data) {
                        data.$event.preventDefault();
                        $vm.currentItem = data.item;
                    }).ready(function () {
                        setTimeout(function () {
                            $vm.loaded = true;
                        }, 100);
                        //
                    });
                },
                methods: {
                    abc: function () {
                        return Math.random();
                    },
                    isShowItem: function (itemId) {
                        return !this.loaded || this.currentItem.id == itemId;
                    },
                    mainClass: function () {
                        var cls = [this.$().attr('data-classes') || '']

                        if (this.loaded) {
                            cls.push('ready');
                        }

                        cls.push(this.abcx)

                        return cls;
                    },
                    _completeItem: function (e) {
                        //$(document).trigger('LP.complete-item', {$event: e, item: this.currentItem});
                        LP_Event_Bus.$emit('complete-item', {$event: e, item: this.currentItem});
                    },
                    $: function (selector) {
                        return selector ? $(this.$el).find(selector) : $(this.$el);
                    },
                    $courseStore: function (prop, value) {
                        var $store = window.$courseStore;

                        console.log('$Store', $store)

                        if (!$store) {
                            return undefined;
                        }

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
        }

        var $vm = xxx();

        $(document).on('course-ready', function () {
            $vm.courseLoaded = true;
        });

        window.$vmLesson = $vm;
    })(jQuery);
</script>
