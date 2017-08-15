<?php
/**
 * Template choose items.
 *
 * @since 3.0.0
 */

$item_types = apply_filters( 'learn-press/course/item-section-types', array(
	'lesson' => __( 'Lesson', 'learnpress' ),
	'quiz'   => __( 'Quiz', 'learnpress' ),
) );

?>

<script type="text/x-template" id="tmpl-lp-course-choose-items">
    <div id="lp-modal-choose-items" :class="{show:show}">
        <div class="lp-choose-items">
            <div class="header">
                <ul class="tabs">
                    <template v-for="(type, key) in types">
                        <li :data-type="key"
                            class="tab"
                            @click.prevent="changeTab(key)"
                            :class="key === tab ? 'active': 'inactive'">
                            <a href="#" @click.prevent="">{{type}}</a>
                        </li>
                    </template>
                </ul>

                <div class="close" @click="close">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
            <div class="main">
                <form class="search" @submit.prevent="">
                    <input placeholder="Type here to search item"
                           title="search"
                           @input="makeSearch"
                           v-model="query">
                </form>

                <ul class="list-items">
                    <template v-for="item in items">
                        <li @click="addItem(item)"><span class="dashicons dashicons-plus"></span>{{item.title}}</li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-curriculum-choose-items', {
            template: '#tmpl-lp-course-choose-items',
            data: function () {
                return {
                    query: '',
                    page: 1,
                    tab: 'lp_lesson',
                    delayTimeout: null
                };
            },
            created: function () {
                var vm = this;

                $store.subscribe(function (mutation) {
                    if (!mutation || mutation.type !== 'TOGGLE_CHOOSE_ITEMS') {
                        return;
                    }

                    if (vm.show) {
                        vm.requestSearch();
                    }
                });
            },
            methods: {
                addItem: function (item) {
                    $store.dispatch('ci/addItem', item);
                },
                close: function () {
                    $store.dispatch('ci/toggle');
                },
                changeTab: function (key) {
                    if (key === this.tab) {
                        return;
                    }

                    this.tab = key;
                    this.requestSearch();
                },
                makeSearch: function () {
                    var vm = this;

                    if (this.delayTimeout) {
                        clearTimeout(this.delayTimeout);
                    }

                    this.delayTimeout = setTimeout(function () {
                        vm.requestSearch();
                    }, 1000);
                },
                requestSearch: function () {
                    $store.dispatch('ci/searchItems', {
                        query: this.query,
                        page: this.page,
                        type: this.tab
                    });
                }
            },
            computed: {
                addedItems: function () {
                    return $store.getters['ci/addedItems'];
                },
                items: function () {
                    return $store.getters['ci/items'];
                },
                show: function () {
                    return $store.getters['ci/isOpen'];
                },
                types: function () {
                    return $store.getters['ci/types'];
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
