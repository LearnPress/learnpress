<?php
$data = wp_parse_args(
	$args,
	[
		'id' => get_the_ID() ?? '',
	]
);

$question_id = $data['id'];

?>

<div class="quiz-question-options js-question-options" data-question-id="<?php echo esc_attr( $question_id ); ?>">
	<div class="lp-place-holder">
		<div class="line-heading"></div>
	</div>
	<div class="postbox closed" style="display:none;">
		<h2 class="lp-box-data-head lp-row hndle">
			<span><?php esc_html_e( 'Question Option', 'learnpress' ); ?></span>
			<div class="status success"></div>
		</h2>
		<a class="toggle"></a>
		<div class="inside">
			<div class="lp-quiz-editor__detail">
				<div class="lp-quiz-editor__detail-field">
					<div class="lp-quiz-editor__detail-label">
						<label> <?php esc_html_e( 'Description', 'learnpress' ); ?></label>
					</div>
					<div class="lp-quiz-editor__detail-input">
						<div><textarea name="" cols="60" rows="3" class="lp-quiz-editor__detail-textarea large-text question-description"></textarea></div>
					</div>
				</div>
				<div class="lp-quiz-editor__detail-field">
					<div class="lp-quiz-editor__detail-label"><label><?php esc_html_e( 'Points', 'learnpress' ); ?></label></div>
					<div class="lp-quiz-editor__detail-input">
						<div><input class="question-points" name="mark" type="number" min="1" step="1">
							<p class="description"><?php esc_html_e( 'Points for choosing the correct answer.', 'learnpress' ); ?></p>
						</div>
					</div>
				</div>
				<div class="lp-quiz-editor__detail-field">
					<div class="lp-quiz-editor__detail-label"><label><?php esc_html_e( 'Hint', 'learnpress' ); ?></label></div>
					<div class="lp-quiz-editor__detail-input">
						<div><textarea name="hint" cols="60" rows="3" class="rlp-quiz-editor__detail-textarea large-text question-hint"></textarea>
							<p class="description">
								<?php esc_html_e( "The instructions for the user to select the right answer. The text will be shown when users click the 'Hint' button.", 'learnpress' ); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="lp-quiz-editor__detail-field">
					<div class="lp-quiz-editor__detail-label"><label><?php esc_html_e( 'Explanation', 'learnpress' ); ?></label></div>
					<div class="lp-quiz-editor__detail-input">
						<div><textarea name="explanation" cols="60" rows="3" class="lp-quiz-editor__detail-textarea large-text question-explanation"></textarea>
							<p class="description">
								<?php esc_html_e( 'The explanation will be displayed when students click the "Check Answer" button.', 'learnpress' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>