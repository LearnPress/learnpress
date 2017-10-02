<?php
/**
 * Template added question items preview.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-added-items-preview">
    <div class="lp-quiz-added-items-preview" :class="{show:show}">
        <ul class="list-added-items">
            <template v-for="(item, index) in addedItems">
                <li class="question-item removable" :class="item.type" @click="removeItem(item)">
                    <span class="icon"></span>
                    <span class="title">{{item.title}}</span>
                </li>
            </template>
        </ul>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-quiz-added-items-preview', {
                template: 'tmpl-quiz-added-items-preview',
                props: {
                    show: {
                        type: Boolean,
                        value: false
                    }
                },
                methods: {
                    removeItem: function (item) {
                        $store.dispatch('cqi/removeItem', item);
                    }
                },
                computed: {
                    addedItem: function () {
                        return $store.getters['cqi/addedItems'];
                    }
                }
            }
        );

    })(Vue, LP_Quiz_Store);
</script>