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
                <div class="row">
                    <div class="col-6">
                        <form class="search" @submit.prevent="">
                            <input placeholder="Type here to search item"
                                   type="text"
                                   title="search"
                                   @input="makeSearch"
                                   v-model="query">
                        </form>

                        <ul class="list-items">
                            <template v-for="item in items">
                                <li class="item-section" @click="addItem(item)" :data-id="item.id">
                                    <span class="btn-add"></span>
                                    <span class="name">{{item.title}}</span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="col-6 added-items">
                        <h4>Added items ({{addedItems.length}})</h4>

                        <ul class="list-added-items">
                            <template v-for="(item, index) in addedItems">
                                <li class="item-section" :class="'type-' + item.type" :key="index">
                                    <span class="icon"></span>
                                    <span class="name">{{item.title}}</span>
                                    <span class="remove" @click="removeItem(index)"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
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
                    if (mutation.type !== 'TOGGLE_CHOOSE_ITEMS') {
                        return;
                    }

                    if (vm.show) {
                        vm.init();
                        vm.makeSearch();
                    }
                });
            },
            methods: {
                init: function () {
                    $store.dispatch('resetAddedItemsCI');
                },
                close: function () {
                    $store.dispatch('toggleChooseItems');
                },
                changeTab: function (key) {
                    this.tab = key;
                    this.makeSearch();
                },
                addItem: function (item) {
                    $store.dispatch('addItemCI', item);
                },
                removeItem: function (index) {
                    $store.dispatch('removeItemCI', index);
                },
                makeSearch: function () {
                    var vm = this;

                    if (this.delayTimeout) {
                        clearTimeout(this.delayTimeout);
                    }

                    this.delayTimeout = setTimeout(function () {
                        $store.dispatch('searchItems', {
                            query: vm.query,
                            page: vm.page,
                            type: vm.tab
                        });
                    }, 1000);
                }
            },
            computed: {
                addedItems: function () {
                    return $store.getters.chooseItems.addedItems;
                },
                items: function () {
                    return $store.getters.chooseItems.items;
                },
                show: function () {
                    return $store.getters.chooseItems.open;
                },
                types: function () {
                    return $store.getters.chooseItems.types;
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
