<?php
/**
 * Preload all items for Vue framework
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.2.0
 */
defined( 'ABSPATH' ) or die;

/**
 * @var LP_Course         $course
 * @var LP_Course_Section $section
 * @var LP_Course_Item    $item
 */
global $lp_course_item;

$course             = LP_Global::course();
$global_course_item = $lp_course_item;

$item_types = learn_press_course_get_support_item_types();
//foreach ( $item_types as $item_type => $name ) {
//	?>
<!--    <script type="text/html" id="tmpl-course-item-content---><?php //echo $item_type; ?><!--">-->
<!--        <div>-->
<!--			--><?php //do_action( 'learn-press/tmpl-course-item-content', $item_type ); ?>
<!--        </div>-->
<!--    </script>-->
<?php //} ?>
<div id="learn-press-content-item">

    <div class="content-item-scrollable">

        <div class="content-item-wrap">

            <div :class="mainClass()"
                 data-classes="<?php echo join( ' ', learn_press_content_item_summary_main_classes() ); ?>">
				<?php
				foreach ( $course->get_sections() as $section ) {
					foreach ( $section->get_items() as $item ) {
						$lp_course_item = $item;
						?>
                        <div id="content-item-<?php echo $item->get_id(); ?>"
                             v-show="isShowItem(<?php echo $item->get_id(); ?>)"
                             class="learn-press-content-item content-item-<?php echo $item->get_post_type(); ?>">
                            <component :is="getComponent('<?php echo $item->get_post_type(); ?>')"
                                       :item="currentItem"
                                       :current-item="currentItem"
                                       :is-current="currentItem.id==<?php echo $item->get_id(); ?>" inline-template>
                                <div class="content-item-content">
									<?php do_action( 'learn-press/vm/course-item-content',  $item->get_id(), $course->get_id() ); ?>
									<?php //do_action( 'learn-press/tmpl-course-item-content-description', $item->get_id(), $course->get_id() ) ?>
                                </div>
                            </component>
                        </div>
						<?php
					}
				}
				?>
            </div>

        </div>

    </div>

</div>

<?php
// Reset global course item
$lp_course_item = $global_course_item;
?>


<script>
    (function ($) {
        function xxx() {
            return new Vue({
                el: '#learn-press-content-item',
                data: function () {
                    return {
                        loaded: false,
                        courseLoaded: false,
                        currentItem: {},
                        item: {a: 0}
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
                    getComponent: function (type) {
                        var component = 'lp-course-item-' + type,
                            refComponent = $(document).triggerHandler('LP.get-course-item-component', component, {type: type});

                        if (refComponent) {
                            component = refComponent;
                        }
                        if (!Vue.options.components[component]) {
                            component = 'lp-course-item';
                            console.log('Vue component ' + component + ' does not exist.');
                        }
                        return component;
                    },
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

                        cls.push(this.currentItem.type)

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
		<?php readfile( LP_PLUGIN_PATH . '/assets/js/frontend/vm.quiz.js' );?>
        var lpQuizQuestions = {};
        var componentDefaults = {
            props: ['item', 'isCurrent', 'currentItem'],
//            functional: true,
//            render: function (createElement, context) {
//                return createElement(
//                    'div',
//                    context.data,
//                    context.children)
//            }
        }
        Vue.component('lp-course-item', $.extend({}, componentDefaults, {
            getComponent: function (type) {
                var component = 'lp-course-item-' + type,
                    refComponent = $(document).triggerHandler('LP.get-course-item-component', component, {type: type});

                if (refComponent) {
                    component = refComponent;
                }
                if (!Vue.options.components[component]) {
                    component = 'lp-course-item';
                    console.log('Vue component ' + component + ' does not exist.');
                }
                return component;
            },
        }));
        Vue.component('lp-course-item-lp_lesson', $.extend({}, componentDefaults, {
            methods: {
                isShowContent: function () {
                    return true;
                }
            }
        }));


        var $vm = xxx();

        $(document).on('course-ready', function () {
            $vm.courseLoaded = true;
        });

        window.$vmContentItem = $vm;

    })(jQuery);
</script>
