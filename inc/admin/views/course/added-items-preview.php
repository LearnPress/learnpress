<?php
/**
 * Template added items preview.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-added-items-preview">
    <div class="lp-added-items-preview">
        <ul class="list-added-items">
            <template v-for="(item, index) in addedItems">
                <li @click="removeItem(index)"><span  v-html="item.title"></span></li>
            </template>
        </ul>

        <div class="actions">
            <span class="total">{{addedItems.length}}</span>
            <span class="close"></span>
        </div>

        <button :disabled="!addedItems.length" type="button" class="button" @click="checkout">Add</button>
    </div>
</script>

<script>
    (function (Vue, $store) {
        Vue.component('lp-added-items-preview', {
            template: '#tmpl-lp-added-items-preview',
            data: function () {
                return {}
            },
            methods: {
                removeItem: function (index) {
                    $store.dispatch('ci/removeItem', index);
                },
                checkout: function () {
                    $store.dispatch('ci/addItemsToSection');
                }
            },
            computed: {
                addedItems: function () {
                    return $store.getters['ci/addedItems'];
                }
            }
        });
    })(Vue, LP_Curriculum_Store);
</script>
