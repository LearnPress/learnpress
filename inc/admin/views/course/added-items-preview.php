<?php
/**
 * Template added items preview.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-added-items-preview">
    <div class="lp-added-items-preview" :class="{show:show}">
        <ul class="list-added-items">
            <template v-for="(item, index) in addedItems">
                <li @click="removeItem(item)" class="section-item removable" :class="item.type">
                    <input type="checkbox" checked>

                    <span class="title">{{item.title}}</span>
                </li>
            </template>
        </ul>
    </div>
</script>

<script type="text/javascript">
    window.$Vue = window.$Vue || Vue;

    jQuery(function() {
        (function ($store) {

            $Vue.component('lp-added-items-preview', {
                template: '#tmpl-lp-added-items-preview',
                props: {
                    show: true
                },
                methods: {
                    removeItem: function (item) {
                        $store.dispatch('ci/removeItem', item);
                    }
                },
                computed: {
                    addedItems: function () {
                        return $store.getters['ci/addedItems'];
                    }
                }
            });
        })(LP_Curriculum_Store);
    });
</script>
