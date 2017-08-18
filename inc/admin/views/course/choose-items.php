<?php
/**
 * Template choose items.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/added-items-preview' );

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
                           @input="onChangeQuery"
                           v-model="query">
                </form>

                <ul class="list-items">
                    <template v-if="!items.length">
                        <div>No any item.</div>
                    </template>

                    <template v-else v-for="item in items">
                        <li @click="addItem(item)"><span class="dashicons dashicons-plus"></span><span
                                    v-html="item.title"></span></li>
                    </template>
                </ul>

                <lp-added-items-preview></lp-added-items-preview>

                <button type="button" class="button" @click="checkout">Add</button>
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
                    if (!mutation || mutation.type !== 'ci/TOGGLE') {
                        return;
                    }

                    if (vm.show) {
                        vm.init();
                    }
                });
            },
            methods: {
                init: function () {
                    this.query = '';
                    this.page = 1;
                    this.tab = this.firstType;
                    this.makeSearch();
                },

                checkout: function () {
                    $store.dispatch('ci/addItemsToSection');
                },

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
                    this.makeSearch();
                },

                onChangeQuery: function () {
                    var vm = this;

                    if (this.delayTimeout) {
                        clearTimeout(this.delayTimeout);
                    }

                    this.delayTimeout = setTimeout(function () {
                        vm.makeSearch();
                    }, 1000);
                },

                makeSearch: function () {
                    $store.dispatch('ci/searchItems', {
                        query: this.query,
                        page: this.page,
                        type: this.tab
                    });
                }
            },
            computed: {
                items: function () {
                    return $store.getters['ci/items'];
                },
                show: function () {
                    return $store.getters['ci/isOpen'];
                },
                types: function () {
                    return $store.getters['ci/types'];
                },
                firstType: function() {
                    for (var type in $store.getters['ci/types']) {
                        return type;
                    }

                    return false;
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
