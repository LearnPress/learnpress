<?php
/**
 * Question answer item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-answer-option">
	<tr class="answer-option" :class="[isNew() || isUpdating() ? 'empty-option' : '']" :data-answer-id="answer.question_answer_id" :data-order-answer="index">
		<td class="sort lp-sortable-handle"><?php learn_press_admin_view( 'svg-icon' ); ?></td>
		<td class="answer-text">
			<input type="text" v-model="answer.title" @change="changeTitle" @keyup.enter="updateTitle" @blur="updateTitle" @keyup="keyUp"/>
		</td>
		<td class="answer-correct lp-answer-check">
			<input :type="radio ? 'radio' : 'checkbox'" :checked="correct" :value="answer.value" :name="name" @change="changeCorrect">
		</td>
		<td class="actions lp-toolbar-buttons">
			<div class="lp-toolbar-btn lp-btn-remove" v-if="deletable">
				<a class="lp-btn-icon dashicons dashicons-trash" @click="deleteAnswer"></a>
			</div>
		</td>
	</tr>
</script>

<script type="text/javascript">
	jQuery( function($) {
		var $Vue = window.$Vue || Vue;
		var $store = window.LP_Quiz_Store;

		$Vue.component('lp-quiz-question-answer-option', {
			template: '#tmpl-lp-quiz-question-answer-option',
			props: ['question', 'answer', 'index'],
			data: function() {
				return {
					title: this.answer.title,
					changed: false,
					updating: false
				}
			},
			watch: {
				status: function (newStatus) {
					if (newStatus !== 'updating') {
						this.updating = false;
					}
					return newStatus;
				}
			},
			computed: {
				// correct answer
				correct: function () {
					return this.answer.is_true === 'yes';
				},
				// radio
				radio: function () {
					var type = this.question.type.key;

					return type === 'true_or_false' || type === 'single_choice';
				},
				// correct answer input name
				name: function () {
					return 'answer_question[' + this.question.id + ']'
				},
				numberCorrect: function () {
					var correct = 0;
					this.question.answers.forEach(function (answer) {
						if (answer.is_true === 'yes') {
							correct += 1;
						}
					});
					return correct;
				},
				// deletable answer option
				deletable: function () {
					return !((this.answer.is_true === 'yes' && this.numberCorrect === 1) || (this.question.type.key === 'true_or_false') || this.question.answers.length < 3);
				},
				status: function () {
					return $store.getters['lqs/statusUpdateQuestionItem'][this.question.id] || '';
				}
			},
			methods: {
				isNew: function () {
					return isNaN(this.answer.question_answer_id)
				},
				changeCorrect: function (e) {
					this.updating = true;

					this.answer.is_true = (e.target.checked) ? 'yes' : '';
					this.$emit('changeCorrect', this.answer);
				},
				// detect change answer title
				changeTitle: function () {
					this.changed = true;
				},
				// update answer option title
				updateTitle: function () {
					if (this.changed) {
						this.updating = true;
						$store.dispatch('lqs/updateQuestionAnswerTitle', {
							question_id: this.question.id,
							answer: this.answer
						});
					}
				},
				// deletable answer
				deleteAnswer: function () {
					$store.dispatch('lqs/deleteQuestionAnswer', {
						question_id: this.question.id,
						answer_id: this.answer.question_answer_id
					});
				},
				isUpdating: function () {
					return this.updating;
				},
				// navigation answer option items
				keyUp: function (event) {
					var keyCode = event.keyCode;
					// escape update answer option items text
					if (keyCode === 27) {
						this.answer.title = this.title;
					} else {
						this.$emit('nav', {key: event.keyCode, order: this.index});
					}
				}
			}
		});
	});
</script>
