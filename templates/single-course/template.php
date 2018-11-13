<?php
/**
 *
 */
$course      = LP_Global::course();
$course_item = LP_Global::course_item();
$context     = $course_item ? 'course-item' : 'course';
?>
<style>
    #learn-press-course-curriculum .curriculum-sections{
        opacity: 0;
    }

    #learn-press-course-curriculum.ready .curriculum-sections{
        opacity: 1;
    }
</style>

<div :class="['course-curriculum', ready ? 'ready' : '']" id="learn-press-course-curriculum"
     data-context="<?php echo $context; ?>">
    <div class="curriculum-scrollable">
        <ul class="curriculum-sections">
            <li v-for="(section, sectionIndex) in $courseStore().sections" :class="sectionClass(section)"
                :id="sectionHtmlId(section)"
                :data-id="section.slug" :data-section-d="section.id">
                <div class="section-header">
                    <div class="section-left">
                        <h5 class="section-title">{{section.name}}</h5>
                        <p v-if="section.desc" class="section-desc">{{section.desc}}</p>
                    </div>
                    <div class="section-meta">
                        <div class="learn-press-progress section-progress" title="7%">
                            <div class="progress-bg">
                                <div class="progress-active primary-background-color"
                                     style="left: 7.69230769231%;"></div>
                            </div>
                        </div>
                        <span class="step">{{getSectionCountItemsHtml(section)}}</span>
                        <span class="collapse"></span>
                    </div>
                </div>
                <ul class="section-content">
                    <li v-for="(item, itemIndex) in section.items" :class="sectionItemClass(item, section)">
                        <a class="section-item-link" :href="item.permalink" @click="_openItem($event, item)">
                            <span class="item-name">{{item.name}} {{item.completed}}</span>
                            <div class="course-item-meta">
                                <i class="fa item-meta course-item-status trans"></i>
                            </div>
                        </a>
                        {{endTime(sectionIndex, itemIndex)}}
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<?php


?>

<script>
    var LP_Course_Settings = {};

    ;(function ($) {
        if (Array.prototype.sum === undefined) {
            Array.prototype.sum = function () {
                return this.reduce(function (a, b) {
                    return a + b;
                }, 0);
            }
        }

        window.LP_Event_Bus = new Vue();

        function xxx() {
            var $request = null;
            var vueConfig = {
                el: '#learn-press-course-curriculum',
                data: function () {
                    return {
                        ready: false
                    }
                },
                created: function () {
                },
                computed: {
                    currentItem: function () {
                        return this.$courseStore('currentItem');
                    }
                },
                watch: {
//                currentItem: {
//                    handler: function (a, b) {
//                        console.log(a, b)
//                        if (a.id == b.id) {
//                            if (a.completed && !b.completed) {
//                                this.completeItem();
//                            }
//                        }
//
//                        return a;
//                    }, deep: true
//                }
                },
                mounted: function () {
                    this.totalItems = $.map(this.sections, function (a) {
                        return a.items.length;
                    }).sum();
                    this._$request = $request;
                    LP_Event_Bus.$on('complete-item', this._completeItem);
                },
                methods: {
                    completeItem: function (item) {
                        item = item || this.currentItem;

                        $request(false, 'complete-course-item', {itemId: item.id}).then(function (r) {
                            if (r.classes) {
                                item.classes = $(r.classes).filter(function (a, b) {
                                    return -1 === $.inArray(b, ['current']);
                                }).get();
                                item.completed = true;
                            }
                        });
                    },
                    sectionClass: function (section) {
                        var cls = ['section'];

                        return cls;
                    },
                    sectionHtmlId: function (section) {
                        return 'section-' + section.slug;
                    },
                    countItems: function (section) {
                        if (!section) {
                            return this.totalItems;
                        }

                        return $.map([section], function (s) {
                            return s.items.length;
                        }).sum()
                    },
                    countCompletedItems: function (section) {
                        if (!section) {
                            section = this.sections;
                        } else {
                            section = [section];
                        }

                        return $.map(section, function (s) {
                            return $.grep(s.items, function (i) {
                                return i.completed;
                            }).length;
                        }).sum()
                    },
                    getSectionCountItemsHtml: function (section) {
                        return this.countCompletedItems(section) + '/' + this.countItems(section);
                    },
                    sectionItemClass: function (item, section) {
                        var cls = $(this.vmArray2Array(item.classes)).filter(function (a, b) {
                            return -1 === $.inArray(b, ['current']);
                        }).get();

                        cls.push('course-item-' + item.type);
                        cls.push('course-item-' + item.id);

                        if (this.currentItem && this.currentItem.id == item.id) {
                            cls.push('current');
                        } else {

                        }

//                    if (item.completed) {
//                        cls.push('has-status status-completed');
//                    }
                        return cls;
                    },
                    vmArray2Array: function (a) {
                        var r = [];
                        for (var i in a) {
                            if (isNaN(i)) {
                                break;
                            }
                            r.push(a[i])
                        }

                        return r;
                    },
                    _openItem: function (e, item) {
                        this.$courseStore().currentItem = item;
                        if (undefined !== $(document).triggerHandler('LP.click-curriculum-item', {
                                $event: e,
                                item: item,
                                $vm: this
                            })) {
                            e.preventDefault();
                        }
                    },
                    _completeItem: function (data) {
                        this.completeItem(data.item || this.currentItem);
                    },
                    $courseStore: function (prop, value) {
                        var $store = window.$courseStore;
                        if (prop) {
                            if (arguments.length == 2) {
                                $store.getters[prop] = value;
                            } else {
                                return $store.getters[prop];
                            }
                        }

                        return $store.getters['all'];
                    },
                    endTime: function (sectionIndex, itemIndex) {
                        var sections = this.$courseStore().sections;
                        if (!this.ready && sectionIndex == sections.length - 1 && itemIndex == sections[sectionIndex].items.length - 1) {
                            this.ready = true;
                            $(document).trigger('course-ready')
                        }
                    }
                }
            };
            console.time('Load curriculum data')

            console.log('aaaa')
            window.LP_Course_Settings = <?php echo json_encode( learn_press_get_course_curriculum_for_js( $course->get_id() ) );?>

            var yyy = function (data) {
                window.$courseStore = (function (data) {
                    var state = data;

                    var getters = {
                        currentItem: function (state) {
                            if (!$.isPlainObject(state.currentItem)) {
                                for (var i = 0, n = state.sections.length; i < n; i++) {
                                    var item = state.sections[i].items.find(function (a) {
                                        return a.id == state.currentItem;
                                    });

                                    if (item) {
                                        state.currentItem = item;
                                        break;
                                    }
                                }
                            }
                            return state.currentItem;
                        },
                        identify: function (state) {
                            return state.identify;
                        },
                        rootUrl: function (state) {
                            return state.rootUrl || '';
                        },
                        all: function (state) {
                            return state;
                        }
                    };
                    var mutations = {};
                    var actions = {};


                    return new Vuex.Store({
                        state: state,
                        getters: getters,
                        mutations: mutations,
                        actions: actions
                    });
                })(data);

                $request = window.$request = new LP.Request($courseStore, {courseId: LP_Course_Settings.courseId});
                window.$vmCourse = new Vue(vueConfig);
                console.timeEnd('Load curriculum data')
            };


            yyy(LP_Course_Settings)

//            $.ajax({
//                url: '',
//                data: {
//                    'lp-ajax': 'load_course_curriculum',
//                    course_ID: <?php //echo $course->get_id();?>
//                },
//                success: function (r) {
//                    window.LP_Course_Settings = LP.parseJSON(r);
//
//                }
//            });

        }

        $(document).ready(function () {
            xxx();
        })

        if (!window.$)
            window.$ = jQuery
    })(jQuery);
</script>