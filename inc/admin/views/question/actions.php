<?php
/**
 * Admin question editor: question actions template.
 *
 * @author ThimPress <nhamdv>
 * @since 4.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-actions">
	<div class="lp-box-data-head lp-row">
		<h3 class="heading"><?php esc_html_e( 'Details', 'learnpress' ); ?></h3>
		<div class="lp-question-editor lp-question-editor--right">
			<div class="lp-question-editor__inner">
				<div class="question-types">
					<a>{{typeLabel()}}</a>
					<ul>
						<li v-for="(type, key) in types" :data-type="key" :class="active(key)">
							<a href="" @click.prevent="changeType(key)">{{type}}</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/javascript">
	jQuery( function( $ ) {
		var $store = window.LP_Question_Store;
		var pick = lodash.pick;

		window.$Vue = window.$Vue || Vue;

		$Vue.component( 'lp-question-actions', {
			template: '#tmpl-lp-question-actions',
			props: ['type'],
			computed: {
				types: function() {
					return $store.getters['types']
				}
			},
			methods: {
				typeLabel: function() {
					var types = this.types;
					return types[this.type];
				},
				active: function( type ) {
					var classes = [''];

					if ( this.type === type ) {
						classes.push('active');
					}

					var supportTypes = $store.getters['supportAnswerOptions'];

					if ( supportTypes.indexOf( type ) === -1 || supportTypes.indexOf( this.type ) === -1 ) {
						classes.push( 'disabled' )
					}

					return classes;
				},
				changeType: function( type ) {
					if ( this.type !== type ) {
						this.$emit( 'changeType', type );
					}
				},
				getQuestionsSupportAnswerOptions: function() {
					var supportTypes = $store.getters['supportAnswerOptions'];

					return supportTypes.indexOf( this.type ) !== -1 ? pick( this.types, supportTypes ) : false;
				}
			}
		});
	});
</script>
