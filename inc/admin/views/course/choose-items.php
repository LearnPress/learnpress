<?php
/**
 * Template choose items.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/added-items-preview' );

?>

<script type="text/x-template" id="tmpl-lp-course-choose-item">
    <li class="section-item" :class="item.type" @click="$emit('add', item)">
        <span class="icon"></span>
        <span class="title">{{item.title}}</span>
    </li>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-course-choose-item', {
            template: '#tmpl-lp-course-choose-item',
            props: ['item']
        });

    })(Vue, LP_Curriculum_Store);
</script>

<script type="text/x-template" id="tmpl-lp-course-choose-items">
    <div id="lp-modal-choose-items" :class="{show:show}">
        <div class="lp-choose-items" :class="{'show-preview': showPreview}">
            <div class="header">
                <div class="preview-title">
                    <span>Selected items ({{addedItems.length}})</span>
                </div>

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
                        <lp-course-choose-item @add="addItem(item)" :item="item"></lp-course-choose-item>
                    </template>
                </ul>

                <div class="pagination" v-if="totalPage > 1">
                    <template v-for="number in totalPage">
                        <span
                                @click="changePage(number)"
                                class="number" :class="number == page ? 'current' : ''">{{number}}</span>
                    </template>
                </div>

                <lp-added-items-preview :show="showPreview"></lp-added-items-preview>
            </div>

            <div class="footer">
                <div class="cart">
                    <button
                            @click="checkout"
                            :disabled="!addedItems.length"
                            type="button"
                            class="button button-primary checkout">
                        <span>Add ({{addedItems.length}})</span>
                    </button>

                    <button type="button"
                            @click.prevent="showPreview = !showPreview"
                            class="button button-secondary edit-selected">
                        {{textButtonEdit}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store, $) {

        Vue.component('lp-curriculum-choose-items', {
            template: '#tmpl-lp-course-choose-items',
            data: function () {
                return {
                    query: '',
                    page: 1,
                    tab: 'lp_lesson',
                    delayTimeout: null,
                    showPreview: false
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

                        $('body').addClass('lp-modal-choose-items-open');
                    } else {
                        $('body').removeClass('lp-modal-choose-items-open');
                    }
                });
            },
            methods: {
                init: function () {
                    this.query = '';
                    this.page = 1;
                    this.tab = this.firstType;
                    this.showPreview = false;
                    this.makeSearch();
                },

                checkout: function () {
                    $store.dispatch('ci/addItemsToSection');
                },

                changePage: function (page) {
                    this.page = page;
                    this.makeSearch();
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

                    this.page = 1;
                }
            },
            computed: {
                textButtonEdit: function () {
                    if (this.showPreview) {
                        return 'Back';
                    }

                    return 'Selected items';
                },
                addedItems: function () {
                    return $store.getters['ci/addedItems'];
                },
                pagination: function () {
                    return $store.getters['ci/pagination'];
                },
                totalPage: function () {
                    if (this.pagination) {
                        return this.pagination.total || 1;
                    }

                    return 1;
                },
                items: function () {
                    return $store.getters['ci/items'];
                },
                show: function () {
                    return $store.getters['ci/isOpen'];
                },
                types: function () {
                    return $store.getters['ci/types'];
                },
                firstType: function () {
                    for (var type in $store.getters['ci/types']) {
                        return type;
                    }

                    return false;
                }
            }
        });

    })(Vue, LP_Curriculum_Store, jQuery);
</script>
