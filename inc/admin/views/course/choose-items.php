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
                            :class="key === tabActive ? 'active': 'inactive'">
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
                    <input placeholder="Type here to search item" type="text" title="search" v-model="query">
                </form>

                <div class="list-items">

                </div>
            </div>
            <div class="footer">
                <button type="button" class="button button-primary">Add</button>
                <button type="button" class="button button-secondary">Add & Close</button>
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
                    tabActive: 'lesson'
                };
            },
            methods: {
                close: function () {
                    $store.dispatch('toggleChooseItems');
                },
                changeTab: function (key) {
                    this.tabActive = key;
                }
            },
            computed: {
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
