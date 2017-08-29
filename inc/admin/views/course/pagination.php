<?php

/**
 * Pagination.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-pagination">
    <div class="pagination" v-if="totalPage > 1">
                    <span class="number first" :class="{current: (page == 1)}"
                          v-if="totalPage > 2"
                          @click="previousFirstPage">«</span>

        <span class="number previous"
              :class="{current: (page == 1)}"
              @click="previousPage"><?php esc_html_e( 'Previous', 'learnpress' ); ?></span>
        <span class="number next"
              :class="{current: (page == totalPage)}"
              @click="nextPage"><?php esc_html_e( 'Next', 'learnpress' ); ?></span>

        <span class="number last" v-if="totalPage > 2"
              :class="{current: (page == totalPage)}"
              @click="nextLastPage">»</span>
    </div>
</script>

<script>
    (function (Vue, $store, $) {

        Vue.component('lp-pagination', {
            template: '#tmpl-lp-pagination',
            props: ['total'],
            data: function () {
                return {
                    page: 1
                }
            },
            computed: {
                totalPage: function () {
                    return this.total;
                }
            },
            methods: {
                update: function () {
                    this.$emit('update', this.page);
                },

                nextPage: function () {
                    if (this.page < this.total) {
                        this.page++;
                        this.update();
                    }
                },

                nextLastPage: function () {
                    if (this.page < this.total) {
                        this.page = this.total;
                        this.update();
                    }
                },

                previousPage: function () {
                    if (this.page > 1) {
                        this.page--;
                        this.update();
                    }
                },

                previousFirstPage: function () {
                    if (this.page > 1) {
                        this.page = 1;
                        this.update();
                    }
                }
            }
        });


    })(Vue, LP_Curriculum_Store, jQuery);
</script>
