<?php
/**
 * Question options template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-meta">
	<div class="quiz-question-options">
		<div class="postbox" @click="openSettings($event)">
			<h2 class="hndle"><span><?php esc_html_e( 'Details', 'learnpress' ); ?></span> </h2>
			<a class="toggle" @click.prevent="openSettings($event)"></a>
			<div class="inside">
				<div class="lp-quiz-editor__detail">
					<div class="lp-quiz-editor__detail-field">
						<div class="lp-quiz-editor__detail-label">
							<label :for="'content-'+question.id"><?php esc_html_e( 'Description', 'learnpress' ); ?></label>
						</div>
						<div class="lp-quiz-editor__detail-input">
							<div>
								<textarea name="" :id="'content-'+question.id" cols="60" rows="3" class="lp-quiz-editor__detail-textarea large-text" @change="updateContent" v-model="question.settings.content"></textarea>
							</div>
						</div>
					</div>
					<div class="lp-quiz-editor__detail-field">
						<div class="lp-quiz-editor__detail-label">
							<label :for="'marking-'+question.id"><?php esc_html_e( 'Points', 'learnpress' ); ?></label>
						</div>
						<div class="lp-quiz-editor__detail-input">
							<div>
								<input name="mark" :id="'marking-'+question.id" type="number" min="1" step="1" v-model="question.settings.mark" @change="updateMeta">
								<p class="description"><?php esc_html_e( 'Points for choosing the correct answer.', 'learnpress' ); ?></p>
							</div>
						</div>
					</div>
					<div class="lp-quiz-editor__detail-field">
						<div class="lp-quiz-editor__detail-label">
							<label :for="'hint-'+question.id"><?php esc_html_e( 'Hint', 'learnpress' ); ?></label>
						</div>
						<div class="lp-quiz-editor__detail-input">
							<div>
								<textarea name="hint" :id="'hint-'+question.id" cols="60" rows="3" class="rlp-quiz-editor__detail-textarea large-text" @change="updateMeta" v-model="question.settings.hint"></textarea>
								<p class="description"><?php esc_html_e( 'Instruction for user to select the right answer. The text will be shown when users click the \'Hint\' button.', 'learnpress' ); ?></p>
							</div>
						</div>
					</div>
					<div class="lp-quiz-editor__detail-field">
						<div class="lp-quiz-editor__detail-label">
							<label :for="'explanation-'+question.id"><?php esc_html_e( 'Explanation', 'learnpress' ); ?></label>
						</div>
						<div class="lp-quiz-editor__detail-input">
							<div>
								<textarea name="explanation" :id="'explanation-'+question.id" cols="60" rows="3" class="lp-quiz-editor__detail-textarea large-text" @change="updateMeta" v-model="question.settings.explanation"></textarea>
								<p class="description"><?php esc_html_e( 'Explanation will be displayed when students click button "Check Answer".', 'learnpress' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/javascript">
	jQuery( function($) {
		var $Vue = window.$Vue || Vue;
		var $store = window.LP_Quiz_Store;

		$Vue.component('lp-quiz-question-meta', {
			template: '#tmpl-lp-quiz-question-meta',
			props: ['question'],
			methods: {
				updateContent: function () {
					$store.dispatch( 'lqs/updateQuestionContent', this.question );
				},
				updateMeta: function(e) {
					$store.dispatch( 'lqs/updateQuestionMeta', {
						question: this.question,
						meta_key: e.target.name
					});
				},
				openSettings: function(e) {
					e.stopPropagation();

					var $root = $( this.$el ).closest( '.question-settings' ),
						$postbox = $root.find( '.postbox' );

					$postbox.removeClass( 'closed' );

					if ( ! $( e.target ).hasClass( 'toggle' ) ) {
						return;
					}

					var isClosed = $root.toggleClass( 'closed' ).hasClass('closed');

					$store.dispatch( 'lqs/updateQuizQuestionsHidden', {
						hidden: $( '.question-settings.closed' ).map( function() {
							return $(this).closest( '.question-item' ).data( 'item-id' );
						}).get()
					});
				}
			}
		});
	});
</script>
