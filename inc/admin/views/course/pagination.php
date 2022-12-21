<?php

/**
 * Pagination.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-pagination">
	<div class="pagination" v-if="total > 1">
		<form prevent.submit="">
			<button class="button first" :disabled="page == 1" v-if="total > 2 && page > 1 && page != 2"
					@click.prevent="previousFirstPage">«
			</button>
			<button class="button previous" :disabled="page == 1"
					@click.prevent="previousPage"><?php echo esc_html_x( 'Previous', 'page-navigation', 'learnpress' ); ?></button>
			<button class="button next" :disabled="page == total"
					@click.prevent="nextPage"><?php echo esc_html_x( 'Next', 'page-navigation', 'learnpress' ); ?></button>
			<button class="button last" :disabled="page == total"
					v-if="total > 2 && page < total && page != (total - 1)"
					@click.prevent="nextLastPage">»
			</button>
			<span class="index">{{page}} / {{total}}</span>
		</form>
	</div>
</script>

<script type="text/javascript">
	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-pagination', {
				template: '#tmpl-lp-pagination',
				props: ['total'],
				data: function () {
					return { page: 1 }
				},
				methods: {
					update: function() {
						this.$emit( 'update', this.page );
					},
					nextPage: function() {
						if ( this.page < this.total ) {
							this.page++;
							this.update();
						}
					},
					nextLastPage: function() {
						if ( this.page < this.total ) {
							this.page = this.total;
							this.update();
						}
					},
					previousPage: function() {
						if ( this.page > 1 ) {
							this.page--;
							this.update();
						}
					},
					previousFirstPage: function() {
						if ( this.page > 1 ) {
							this.page = 1;
							this.update();
						}
					}
				}
			});
		})( LP_Curriculum_Store );
	});
</script>
