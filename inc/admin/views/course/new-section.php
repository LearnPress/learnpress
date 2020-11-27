<?php

/**
 * Template new section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section">
	<div class="section new-section">
		<form @submit.prevent="">
			<div class="section-head">
				<span class="creatable"></span>
				<input v-model="section_title" type="text" title="title" class="title-input" placeholder="<?php esc_attr_e( 'Create a new section', 'learnpress' ); ?>" @keyup.enter.prevent="newSection">
			</div>
		</form>
	</div>
</script>

<script type="text/javascript">
	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-new-section', {
				template: '#tmpl-lp-new-section',
				data: function() {
					return {
						section_title: ''
					};
				},
				methods: {
					draftCourse: function() {
						if ( $store.getters['autoDraft'] ) {
							$store.dispatch( 'draftCourse', {
								title: $('input[name=post_title]').val(),
								content: $('textarea[name=content]').val()
							});
						}
					},
					newSection: function() {
						if ( this.section_title ) {
							this.draftCourse();

							$store.dispatch( 'ss/newSection', this.section_title );
							this.section_title = '';
						}
					}
				}
			});
		})( LP_Curriculum_Store );
	});
</script>
