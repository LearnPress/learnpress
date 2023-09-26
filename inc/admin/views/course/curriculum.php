<?php
/**
 * Template curriculum course.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/sections' );
?>

<script type="text/x-template" id="tmpl-lp-course-curriculum">
	<div class="lp-course-curriculum">
		<div class="heading">
			<h4><?php esc_html_e( 'Details', 'learnpress' ); ?> <span :class="['status', status]"></span></h4>
			<div class="section-item-counts"><span>{{countAllItems()}}</span></div>
			<span class="collapse-sections" @click="toggle" :class="isOpen ? 'open' : 'close'"></span>
		</div>
		<lp-list-sections></lp-list-sections>
	</div>
</script>

<script type="text/javascript">

	window.$Vue = window.$Vue = Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-curriculum', {
				data: function() {
					return {
						indexOfSection: 0,
						sections_items: [],
					}
				},
				template: '#tmpl-lp-course-curriculum',
				computed: {
					status: function() {
						return $store.getters.status;
					},
					isOpen: function() {
						return ! $store.getters['ss/isHiddenAllSections'];
					}
				},
				methods: {
					toggle: function() {
						$store.dispatch( 'ss/toggleAllSections' );
					},
					countAllItems: function() {
						var count = 0,
							labels = $store.getters['i18n/all'].item_labels;

						$.each( $store.getters['ss/sections'], function ( i, section ) {
							count += section.items.length;
						});

						return count + ' ' + (count <= 1 ? labels.singular : labels.plural);
					},
					add: function ( indexOfSection ){
						setTimeout( () => {
							const dataSet = this.sections_items[ indexOfSection ];
							if ( 'undefined' !== typeof dataSet ) {
								$store.state.ss.sections.push( dataSet );
								this.indexOfSection++;
								if ( this.sections_items.length > this.indexOfSection ) {
									this.add( this.indexOfSection );
								}
							}
						}, 100 );
					}
				},
				beforeMount: function() {
					this.sections_items = lpAdminCourseEditorSettings.sections.sections || [];
					if ( this.sections_items.length > 0 ) {
						$store.state.ss.sections = [ this.sections_items[0] ];
						this.indexOfSection++;
					}
				},
				mounted: function() {
					this.firstLoad = false;
					if ( this.sections_items.length > 1 ) {
						this.add( this.indexOfSection );
					}
				}

			});

		})( LP_Curriculum_Store );
	});
</script>
