<?php
/**
 * Template choose quiz pagination.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-pagination">
	<div id="lp-quiz-pagination" class="pagination" v-if="totalPage > 1">
		<form prevent.submit="">
			<button class="button first" :disabled="page == 1" v-if="total > 3 && page > 1 && page != 2"
					@click.prevent="previousFirstPage">«
			</button>
			<button class="button previous" :disabled="page == 1"
					@click.prevent="previousPage"><?php echo esc_html_x( 'Previous', 'page-navigation', 'learnpress' ); ?></button>
			<button class="button next" :disabled="page == total"
					@click.prevent="nextPage"><?php echo esc_html_x( 'Next', 'page-navigation', 'learnpress' ); ?></button>
			<button class="button last" :disabled="page == total"
					v-if="total > 3 && page < total && page != (total - 1)"
					@click.prevent="nextLastPage">»
			</button>
			<span class="index">{{page}} / {{total}}</span>
		</form>
	</div>
</script>

<script type="text/javascript">
	jQuery(function ($) {
		var $Vue = window.$Vue || Vue;
		var $store = window.LP_Quiz_Store;

		$Vue.component('lp-quiz-pagination', {
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

	})
</script>
