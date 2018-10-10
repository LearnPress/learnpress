<?php
/**
 *
 */
$course      = LP_Global::course();
$course_item = LP_Global::course_item();
$context     = $course_item ? 'course-item' : 'course';
?>
<style>
    #learn-press-course-curriculum {
        opacity: 0;
    }

    #learn-press-course-curriculum.ready {
        opacity: 1;
    }
</style>
[
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
</div>]


<div id="app">
    XXXX
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
        console.time('render curriculum');
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
            mounted: function () {
                this.totalItems = $.map(this.sections, function (a) {
                    return a.items.length;
                }).sum();
            },
            methods: {
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
                    var cls = ['course-item'];
                    cls.push('course-item-' + item.type);
                    cls.push('course-item-' + item.id);

                    if (this.currentItem && this.currentItem.id == item.id) {
                        cls.push('current');
                    }
                    return cls;
                },
                _openItem: function (e, item) {
                    e.preventDefault();
                    this.$courseStore().currentItem = item;
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
                    if (!this.ready && sectionIndex == this.$courseStore().sections.length - 1 && itemIndex == this.$courseStore().sections[sectionIndex].items.length - 1) {
                        this.ready = true;
                        console.timeEnd('render curriculum');

                        $(document).trigger('course-ready')
                    }
                }
            }
        };

        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'load_course_curriculum',
                course_ID: <?php echo $course->get_id();?>
            },
            success: function (r) {
                window.LP_Course_Settings = LP.parseJSON(r);

                window.$courseStore = (function (data) {
                    var state = data;

                    var getters = {
                        currentItem: function (state) {
                            if (!$.isPlainObject(state.currentItem)) {
                                for (var i = 0, n = state.sections.length; i < n; i++) {
                                    var item = state.sections[i].items.find(function (a) {
                                        return a.id == state.currentItem;
                                    });

                                    console.log(item)
                                    if (item) {
                                        state.currentItem = item;
                                        break;
                                    }
                                }
                            }
                            return state.currentItem;
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
                })(LP_Course_Settings);

                window.$lpCourseApp = new Vue(vueConfig);
            }
        });


        if (!window.$)
            window.$ = jQuery
    })(jQuery);
</script>