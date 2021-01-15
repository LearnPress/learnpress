<?php
/**
 * Admin question editor: editor template.
 *
 * @since 3.0.0
 */

$question = LP_Question::get_question();

learn_press_admin_view( 'question/actions' );
learn_press_admin_view( 'question/answer' );
learn_press_admin_view( 'question/fill-in-blanks' );
?>

<div id="admin-editor-lp_question">
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

<script type="text/x-template" id="tmpl-lp-question-editor">
	<div>
	<template v-if="!supportAnswerOptions">
		<?php do_action( 'learn-press/question-editor/question-js-component', $question ); ?>
	</template>
	<template v-else>
		<div id="admin-editor-lp_question" :class="['lp-admin-editor learn-press-box-data', type]">
			<lp-question-actions :type="type" @changeType="changeType"></lp-question-actions>

			<template v-if="isFillInBlank">
				<lp-fib-question-answer :type="type" :answers="answers"></lp-fib-question-answer>
			</template>
			<template v-else>
				<lp-question-answer :type="type" :answers="answers"></lp-question-answer>
			</template>
		</div>
	</template>
	</div>
</script>

<script type="text/javascript">
	jQuery( function($) {
		var $store = window.LP_Question_Store;

		window.$Vue = window.$Vue || Vue;

		$Vue.component('lp-question-editor', {
			template: '#tmpl-lp-question-editor',
			mounted: function() {
				var vm = this;

				this.$watch('type', function() {
				});
			},
			computed: {
				type: function() {
					return $store.getters['type']['key'];
				},
				isFillInBlank: function() {
					return this.type === 'fill_in_blanks';
				},
				supportAnswerOptions: function () {
					return $store.getters['supportAnswerOptions'].indexOf( this.type ) !== -1;
				},
				answers: function() {
					return $store.getters['answers'];
				}
			},
			created: function() {
			},
			methods: {
				changeType: function (type) {
					$store.dispatch('changeQuestionType', {
						question: {
							title: $('input[name=post_title]').val(),
							content: $('textarea[name=content]').val()
						},
						type: type
					});
				}
			}
		});
	});
</script>
