<?php
/**
 * Template choose quiz pagination.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-pagination">
    <div id="lp-quiz-pagination" class="pagination" v-if="totalPage > 1">
        <span class="number previous" :class="{current: (page==1)}" @click="previousPage">
            <?php esc_html_e( 'Previous', 'learnpress' ); ?>
        </span>
        <span class="number next" :class="{current: (page==totalPage)}" @click="nextPage">
            <?php esc_html_e( 'Next', 'learnpress' ); ?>
        </span>
        <span class="number last" v-if="totalPage > 2" :class="{current: (page == totalPage)}"
              @click="nextLastPage">»</span>
    </div>
</script>

<script>
    (function (Vue, $store, $) {

        Vue.component('lp-quiz-pagination', {
            template: '#tmpl-lp-quiz-pagination',
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

    })(Vue, LP_Quiz_Store, jQuery)
</script>
