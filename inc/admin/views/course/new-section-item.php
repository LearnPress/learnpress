<?php

/**
 * Template new item section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section-item">
    <div class="empty-section-item section-item">
        <div class="types">
            <template v-for="(_type, key) in types">
                <label class="type" :class="type==key ? 'current' : ''">
                    {{_type}}
                    <input v-model="type" type="radio" name="lp-section-item-type" :title="_type" :value="key">
                </label>
            </template>
        </div>
        <div class="title">
            <input type="text" placeholder="Type the title" @keyup.enter="createItem" v-model="title">
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-new-section-item', {
            template: '#tmpl-lp-new-section-item',
            props: [],
            data: function () {
                return {
                    type: '',
                    title: ''
                };
            },
            created: function () {
                this.type = this.firstType;
            },
            methods: {
                createItem: function () {
                    this.$emit('create', {
                        type: this.type,
                        title: this.title
                    });

                    this.title = '';
                }
            },
            computed: {
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

    })(Vue, LP_Curriculum_Store);
</script>
