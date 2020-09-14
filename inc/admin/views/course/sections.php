<?php
/**
 * Template list sections.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section' );
learn_press_admin_view( 'course/new-section' );
?>

<script type="text/x-template" id="tmpl-lp-list-sections">
	<div class="curriculum-sections">
		<lp-section v-for="(section, index) in sections" :section="section" :index="index" :disableCurriculum="disableCurriculum" :key="index"></lp-section>

		<div class="add-new-section" v-if="!disableCurriculum">
			<lp-new-section></lp-new-section>
		</div>
	</div>
</script>

<script type="text/javascript">
	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-list-sections', {
				template: '#tmpl-lp-list-sections',
				computed: {
					sections: function() {
						return $store.getters['ss/sections'];
					},
					disableCurriculum: function() {
						return $store.getters['disable_curriculum'];
					}
				},
				created: function() {
					var _self = this;

					setTimeout( function() {
						var $el = $( '.curriculum-sections' );

						$el.sortable({
							handle: '.section-head .movable',
							axis: 'y',
							items: "> .section",
							update: function () {
								_self.sort();
							}
						});
					}, 1000 );
				},
				methods: {
					sort: function() {
						var _items = $( '.curriculum-sections>div.section' ),
							order = _items.map( function() {
								return $( this ).data( 'section-id' );
							}).get();

						$store.dispatch( 'ss/updateSectionsOrder', order );
					}
				}
			});
		})( LP_Curriculum_Store );
	});
</script>
