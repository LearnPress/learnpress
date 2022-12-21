<?php

/**
 * Template new item section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section-item">
	<div class="new-section-item section-item" @keyup.up="up" @keyup.down="down" :class="{choosing: choosingType}">
		<div class="drag lp-sortable-handle"></div>
		<div class="types" @mouseleave="mouseLeave" @mouseover="mouseOver">
			<template v-for="(_type, key) in types">
				<label class="type" :title="_type" :class="[key, {current: (type==key)}]">
					<input v-model="type" type="radio" name="lp-section-item-type" :value="key">
				</label>
			</template>
		</div>
		<div class="title">
			<input type="text" :placeholder="placeholderInput" @keyup.enter="createItem($event)" @blur="createItem($event)" v-model="title">
		</div>
	</div>
</script>

<script type="text/javascript">
	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-new-section-item', {
				template: '#tmpl-lp-new-section-item',
				props: [],
				data: function() {
					return {
						type: '',
						title: '',
						choosingType: false
					};
				},
				created: function() {
					this.type = this.firstType;
				},
				methods: {
					up: function( e ) {
						this.changeType(true);
					},
					down: function( e ) {
						this.changeType(false);
					},
					mouseOver: function() {
						this.choosingType = true;
					},
					mouseLeave: function() {
						this.choosingType = false;
					},
					create: function() {
						if (!this.title) {
							return;
						}
						this.$emit( 'create', {
							id: LP.uniqueId(),
							type: this.type,
							title: this.title
						});
						this.title = '';
					},
					createItem: function( e ) {
						if ( ! this.title ) {
							return;
						}

						if ( e.key === 'Enter' ) {
							return this.create();
						}

						setTimeout(this.create, 300);
					},
					changeType: function (next) {
						if ( this.title ) {
							return;
						}

						var types = this.types;
						var current = this.type;
						var currentIndex = false;

						var keys = [];
						var i = 0;
						for ( var type in types ) {
							if ( type === current ) {
								currentIndex = i;
							}

							keys.push( type );
							i++;
						}

						var nextType = keys[currentIndex + 1] || keys[0];
						var previousType = keys[currentIndex - 1] || keys[keys.length - 1];

						if ( next ) {
							this.type = nextType;
						} else {
							this.type = previousType;
						}

					}
				},
				computed: {
					placeholderInput: function( e ) {
						var i18n = $store.getters['i18n/all'];
						var type = this.types[this.type] || '';

						$( this.$el ).find( '.title input' ).focus();
						return i18n.new_section_item + ' ' + type.toLowerCase();
					},

					types: function() {
						return $store.getters['ci/types'];
					},
					firstType: function() {
						for ( var type in $store.getters['ci/types'] ) {
							return type;
						}

						return false;
					}
				}
			});
		})( LP_Curriculum_Store );
	});
</script>
