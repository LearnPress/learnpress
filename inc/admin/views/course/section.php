<?php
/**
 * Section template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section-item' );
learn_press_admin_view( 'course/new-section-item' );
?>

<script type="text/x-template" id="tmpl-lp-section">
	<div class="section" :class="[isOpen ? 'open' : 'close', status, isEmpty ? 'empty-section' : '']" :data-section-order="index" :data-section-id="section.id">
		<div class="section-head" @dblclick="toggle">
			<span class="movable lp-sortable-handle"></span>
			<!--Section title-->
			<input v-model="section.title" type="text" title="title" class="title-input" @change="updating" @blur="completed" @keyup.enter="completed" placeholder="<?php esc_attr_e( 'Create a new section', 'learnpress' ); ?>">
			<div class="section-item-counts">
				<span v-for="item in countItems()">{{item.count}} {{item.name}}</span>
			</div>
			<!--Section toggle-->
			<div class="actions">
				<span class="collapse" :class="isOpen ? 'open' : 'close'" @click.prevent="toggle"></span>
			</div>
		</div>

		<div class="section-collapse" ref="collapse">
			<div class="section-content">
				<div class="details">
					<!--Section description-->
					<input v-model="section.description" type="text" class="description-input no-submit" title="description" @change="updating" @blur="completed" @keyup.enter="completed" ref="description" placeholder="<?php esc_attr_e( 'Section description...', 'learnpress' ); ?>">
				</div>

				<div class="section-list-items" :class="{'no-item': !section.items.length}">
					<ul>
						<!--Section items-->
						<lp-section-item v-for="(item, index) in section.items" :item="item" :key="item.id" @update="updateItem" @remove="removeItem" @delete="deleteItem" @nav="navItem" :order="index+1" :ref="index+1" :disableCurriculum="disableCurriculum"></lp-section-item>
					</ul>

					<lp-new-section-item @create="newItem" v-if="!disableCurriculum"></lp-new-section-item>
				</div>
			</div>

			<div class="section-actions" v-if="!disableCurriculum">
				<button type="button" class="button button-secondary" @click="openModal"><?php esc_html_e( 'Select items', 'learnpress' ); ?></button>

				<div class="remove" :class="{confirm: confirm}">
					<span class="icon" @click="removing"><?php esc_html_e( 'Delete', 'learnpress' ); ?></span>
					<div class="confirm" @click="remove"><?php esc_html_e( 'Are you sure?', 'learnpress' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/javascript">
	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-section', {
				template: '#tmpl-lp-section',
				props: ['section', 'index', 'disableCurriculum'],
				data: function() {
					return {
						changed: false,
						confirm: false
					};
				},
				mounted: function() {
					var vm = this;

					this.prepareToggle();

					this.$watch( 'section.open', function( open ) {
						vm.toggleAnimation( open );
					});

					$( this.$el ).find( '.section-list-items ul' ).sortable({
						axis: 'y',
						//connectWith: '.section-list-items ul',
						handle: '.drag',
						start: function( e, ui ) {
							var id = parseInt( ui.item.attr( 'data-item-id' ) );

							ui.item.data( 'vmItem', vm.section.items.filter( function( vmItem, i ) {
								return vmItem.id == id
							}))
						},
						update: function( e, ui ) {
							var itemIds = $( this ).children().map( function() {
								return parseInt($(this).attr( 'data-item-id' ) );
							}).get();

							var vmItem = ui.item.data( 'vmItem' );
							vm.updateOrderItems( itemIds, vmItem ? vmItem[0] : null );
						}
					});

					this.$nextTick( function() {
						var $ = jQuery;

						$( this.$el ).find( '.lp-title-attr-tip' ).LP( 'QuickTip',{
							closeInterval: 0,
							arrowOffset: 'el',
							tipClass: 'preview-item-tip'
						});

						$( document ).on( 'mousedown', '.section-item .drag', function( e ) {
							$( 'html, body' ).addClass( 'moving' );
						}).on( 'mouseup', function( e ) {
							$( 'html, body' ).removeClass( 'moving' );
						});
					});
				},
				computed: {
					status: function() {
						return $store.getters['ss/statusUpdateSection'][this.section.id] || '';
					},
					isEmpty: function() {
						return isNaN( this.section.id );
					},
					isOpen: function() {
						return this.section.open;
					},
					items: {
						get: function() {
							return this.section.items;
						},
						set: function( items ) {
							this.section.items = items;

							$store.dispatch( 'ss/updateSectionItems', {
								section_id: this.section.id,
								items: items
							});
						}
					},
					optionDraggable: function() {
						return {
							handle: '.drag',
							draggable: '.section-item',
							group: {
								name: 'lp-section-items',
								put: true,
								pull: true
							}
						};
					}
				},
				methods: {
					updateOrderItems: function( orders, vmItem ) {
						var allItems = JSON.parse( JSON.stringify( this.section.items ) ),
							items = [];

						if ( vmItem ) {
							allItems.push( vmItem );
						}

						for ( var i = 0, n = orders.length; i < n; i++ ) {
							$.each( allItems, function( j, item ) {
								if ( orders[i] === item.id ) {
									items.push( JSON.parse( JSON.stringify( item ) ) );
									found = true;
									return false;
								}
							});
						}

						this.section.items = items;
						$store.dispatch( 'ss/updateSectionItems', {
							section_id: this.section.id,
							items: items
						});
					},
					toggle: function() {
						$store.dispatch( 'ss/toggleSection', this.section );
					},
					prepareToggle: function() {
						var display = 'none';
						if ( this.isOpen ) {
							display = 'block';
						}

						this.$refs.collapse.style.display = display;
					},
					toggleAnimation: function( open ) {
						if ( open ) {
							$( this.$refs.collapse ).slideDown();
						} else {
							$( this.$refs.collapse ).slideUp();
						}
					},
					updating: function() {
						this.changed = true;
					},
					completed: function() {
						if ( this.changed ) {
							$store.dispatch( 'ss/updateSection', this.section );
							this.changed = false;
						}
					},
					removing: function() {
						this.confirm = true;
						var vm = this;

						setTimeout( function() {
							vm.confirm = false;
						}, 3000 );
					},
					remove: function() {
						if ( this.confirm ) {
							$store.dispatch( 'ss/removeSection', { index: this.index, section: this.section } );
							this.confirm = false;
						}
					},
					updateItem: function( item ) {
						$store.dispatch( 'ss/updateSectionItem', { section_id: this.section.id, item: item } );
					},
					removeItem: function( item ) {
						$store.dispatch( 'ss/removeSectionItem', { section_id: this.section.id, item: item } );
					},
					deleteItem: function( item ) {
						$store.dispatch( 'ss/deleteSectionItem', { section_id: this.section.id, item: item } );
					},
					navItem: function( payload ) {
						var keyCode = payload.key,
							order = payload.order;

						if ( keyCode === 38 ) {
							if ( order === 1 ) {
								this.$refs.description.focus();
							} else {
								this.nav( order - 1 );
							}
						}

						if ( keyCode === 40 || keyCode === 13 ) {
							if ( order === this.section.items.length ) {
								// code
							} else {
								this.nav( order + 1 );
							}
						}

					},
					nav: function( position ) {
						var element = 'div[data-section-order=' + this.index + '] li[data-item-order=' + position + ']';
						( $( element ).find( '.title input' ) ).focus();
					},
					newItem: function( item ) {
						$store.dispatch( 'ss/newSectionItem', { section_id: this.section.id, item: item } );
					},
					openModal: function() {
						$store.dispatch( 'ci/open', parseInt( this.section.id ) );
					},
					countItems: function() {
						var count = this.section.items.length,
							labels = $store.getters['i18n/all'].item_labels;
						return [{
							count: count,
							name: count > 1 ? labels.plural : labels.singular
						}];
					}
				}
			});
		})(LP_Curriculum_Store);
	});
</script>
