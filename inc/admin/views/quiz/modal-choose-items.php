<?php
/**
 * Template choose quiz question items.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/added-items-preview' );
learn_press_admin_view( 'quiz/pagination' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-choose-item">
	<li class="question-item" :class="[item.type, item.added ? 'added': 'addable']" @click="add">
		<input type="checkbox" :checked="item.added === true">
		<span class="title">{{item.title}} <strong>(#{{item.id}})</strong></span>
	</li>
</script>

<script type="text/javascript">
	jQuery( function($) {
		var $Vue = window.$Vue || Vue;
		var $store = window.LP_Quiz_Store;

		$Vue.component('lp-quiz-choose-item', {
			template: '#tmpl-lp-quiz-choose-item',
			props: ['item', 'added'],
			watch: {
				added: function () {
					this.$forceUpdate();
				}
			},
			methods: {
				add: function() {
					if ( this.item.added ) {
						return this.remove();
					}

					this.$emit('add', this.item);
				},
				remove: function() {
					this.$emit('remove', this.item);
				}
			}
		});
	});
</script>

<script type="text/x-template" id="tmpl-lp-quiz-choose-items">
	<div id="lp-modal-choose-items" :class="{show:show, loading: loading}">
		<div class="lp-choose-items" :class="{'show-preview': showPreview}">
			<div class="header">
				<div class="preview-title"><span><?php esc_html_e( 'Selected items', 'learnpress' ); ?>
						({{addedItems.length}})</span></div>
				<ul class="tabs">
					<li class="tab active"><a href="#"><?php esc_html_e( 'Questions', 'learnpress' ); ?></a></li>
				</ul>
				<a class="close" @click="close">
					<span class="dashicons dashicons-no-alt" title="<?php esc_attr_e( 'Close', 'learnpress' ); ?>"></span>
				</a>
			</div>

			<div class="main">
				<form class="search" @submit.prevent="">
					<input type="text" class="modal-search-input" placeholder="<?php esc_attr_e( 'Type here to search for the question', 'learnpress' ); ?>" @input="onChangeQuery" v-model="query">
				</form>

				<ul class="list-items">
					<template v-if="!items.length">
						<div><?php esc_html_e( 'No item found.', 'learnpress' ); ?></div>
					</template>
					<template v-for="item in items">
						<lp-quiz-choose-item :added="item.added" :item="item" @add="addItem" @remove="removeItem"></lp-quiz-choose-item>
					</template>
				</ul>

				<lp-quiz-pagination :total="totalPage" @update="changePage"></lp-quiz-pagination>
				<lp-quiz-added-items-preview :show="showPreview"></lp-quiz-added-items-preview>
			</div>

			<div class="footer">
				<div class="cart">
					<button type="button" class="button button-primary checkout" @click="checkout" :disabled="!addedItems.length || adding">
						<span v-if="!adding"><?php esc_html_e( 'Add', 'learnpress' ); ?></span>
						<span v-if="adding"><?php esc_html_e( 'Adding', 'learnpress' ); ?></span>
					</button>

					<button type="button" class="button button-secondary edit-selected" @click.prevent="showPreview = !showPreview" :disabled="adding || (!addedItems.length && !showPreview)">
						{{editCartButton}}
					</button>
				</div>
			</div>
		</div>
	</div>
</script>

<script>
	jQuery( function($) {
		var $Vue = window.$Vue || Vue;
		var $store = window.LP_Quiz_Store;

		$Vue.component('lp-quiz-choose-items', {
			template: '#tmpl-lp-quiz-choose-items',
			data: function () {
				return {
					query: '',
					page: 1,
					delayTimeOut: null,
					showPreview: false,
					adding: false
				}
			},
			created: function () {
				var vm = this;

				$store.subscribe(function (mutation) {
					if (!mutation || mutation.type !== 'cqi/TOGGLE') {
						return;
					}

					if (vm.show) {
						vm.init();

						$('body').addClass('lp-quiz-modal-choose-items-open');
					} else {
						$('body').removeClass('lp-quiz-modal-choose-item-open');
					}
				})
			},
			mounted: function () {

			},
			computed: {
				status: function () {
					return $store.getters['cqi/status'];
				},
				loading: function () {
					return this.status === 'loading';
				},
				editCartButton: function () {
					if (this.showPreview) {
						return $store.getters['i18n/all'].back;
					}

					return $store.getters['i18n/all'].selected_items + ' (' + this.addedItems.length + ')';
				},
				addedItems: function () {
					return $store.getters['cqi/addedItems'];
				},
				pagination: function () {
					return $store.getters['cqi/pagination'];
				},
				totalPage: function () {
					if (this.pagination) {
						return parseInt(this.pagination.total) || 1;
					}
				},
				items: function () {
					return $store.getters['cqi/items'];
				},
				show: function () {
					var isShow = $store.getters['cqi/isOpen'];

					isShow && this.focusInput();

					return isShow;
				},
				// check new quiz
				new_quiz: function () {
					return $store.getters['autoDraft'];
				}
			},
			methods: {
				init: function () {
					this.query = '';
					this.page = 1;
					this.showPreview = false;
					this.adding = false;
					this.makeSearch();
				},
				focusInput: function () {
					var $input = $(this.$el).find('.main .search input[type="text"]').focus();
					setTimeout(function () {
						$input.focus();
					}, 300)
				},
				checkout: function () {
					this.adding = true;
					this.$emit('addItems', this.page);
				},
				changePage: function (page) {
					if (page === this.page) {
						return;
					}
					this.page = page;
					this.makeSearch();
				},
				addItem: function(item) {
					$store.dispatch('cqi/addItem', item);
				},

				removeItem: function(item) {
					$store.dispatch('cqi/removeItem', item);
				},

				close: function() {
					$store.dispatch('cqi/toggle');
				},

				onChangeQuery: function() {
					var vm = this;

					if ( this.delayTimeOut ) {
						clearTimeout( this.delayTimeOut );
					}

					this.delayTimeOut = setTimeout( function() {
						vm.page = 1;
						vm.makeSearch();
					}, 500 );
				},

				makeSearch: function() {
					$store.dispatch('cqi/searchItems', {
						query: this.query,
						page: this.page
					});
				}
			}
		});
	});
</script>
