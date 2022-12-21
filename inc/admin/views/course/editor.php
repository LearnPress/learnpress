<?php
/**
 * Course editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/curriculum' );
learn_press_admin_view( 'course/modal-choose-items' );
?>

<div id="admin-editor-lp_course">
	<div class="lp-place-holder">
		<div class="line-heading"></div>

		<div class="line-sm"></div>
		<div class="line-xs"></div>

		<div class="line-df"></div>
		<div class="line-lgx"></div>
		<div class="line-lg"></div>

		<div class="line-df"></div>
		<div class="line-lg"></div>
		<div class="line-lgx"></div>
	</div>
</div>

<script type="text/x-template" id="tmpl-lp-course-editor">
	<div id="admin-editor-lp_course" class='lp-admin-editor'>
		<div v-if="heartbeat">
			<form @submit.prevent="">
				<lp-curriculum></lp-curriculum>
			</form>

			<lp-curriculum-choose-items></lp-curriculum-choose-items>
		</div>
		<div v-else>
			<div class="lp-place-holder">
				<?php learn_press_admin_view( 'placeholder-animation' ); ?>
				<div class="notify-reload"><?php esc_html_e( 'Something went wrong! Please reload to continue editing the curriculum.', 'learnpress' ); ?></div>
			</div>
		</div>
	</div>
</script>

<script type="text/javascript">

	window.$Vue = window.$Vue || Vue;

	jQuery( function( $ ) {
		( function( $store ) {
			$Vue.component( 'lp-course-editor', {
				template: '#tmpl-lp-course-editor',
				created: function() {
					/*setInterval( function() {
						$store.dispatch( 'heartbeat' );
					}, 60 * 1000);*/
				},
				computed: {
					heartbeat: function() {
						return $store.getters['heartbeat'];
					}
				}
			});
		})(LP_Curriculum_Store);
	});
</script>
