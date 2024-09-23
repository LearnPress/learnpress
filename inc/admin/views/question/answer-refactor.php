<?php
/**
 * Admin Answer item: Editor template.
 *
 * @since 4.2.7
 * @author VuxMinhThanh
 */
$data    = wp_parse_args(
	$args,
	[
		'id'      => '',
		'open'    => false,
		'title'   => 'New Answer',
		'type'    => array(),
		'answers' => array(),
	]
);
$answers = $data['answers'];

$data_setting = [
	'true_or_false' => [
		'input'   => 'radio',
		'add_new' => false,
	],
	'multi_choice'  => [
		'input'   => 'checkbox',
		'add_new' => true,
	],
	'single_choice' => [
		'input'   => 'radio',
		'add_new' => true,
	],
];

apply_filters( 'learn-press/question-setting', $data_setting )
?>
<div class="">
	<table class="lp-list-options list-question-answers">
		<thead>
			<tr>
				<th class="sort"></th>
				<th class="answer-text"><?php esc_html_e( 'Answers', 'learnpress' ); ?></th>
				<th class="answer-correct"><?php esc_html_e( 'Correction', 'learnpress' ); ?></th>
				<th class="actions"></th>
			</tr>
		</thead>
		<tbody class="ui-sortable">
			<?php foreach ( $answers as $key => $answer ) : ?>
				<tr data-answer-id="<?php echo esc_attr( $answer['question_answer_id'] ); ?>" class="answer-option">
					<td class="sort lp-sortable-handle ui-sortable-handle">
						<?php learn_press_admin_view( 'svg-icon' ); ?>
					</td>
					<td class="answer-text">
						<form><input type="text" value="<?php echo esc_attr( $answer['title'] ) ?? ''; ?>"></form>
					</td>
					<td class="answer-correct lp-answer-check">
						<input type="<?php echo esc_attr( $data_setting[ $data['type']['key'] ]['input'] ); ?>" <?php empty( $answer['is_true'] ) ? '' : esc_html_e( 'checked' ); ?> name="answer_question[<?php echo esc_attr( $data['id'] ); ?>]" value="<?php echo esc_attr( $answer['value'] ); ?>">
					</td>
					<td class="actions lp-toolbar-buttons">
						<div class="lp-toolbar-btn lp-btn-remove remove-answer">
							<a title="Delete" class="lp-btn-icon dashicons dashicons-trash"></a>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ( $data_setting[ $data['type']['key'] ]['add_new'] ) : ?>
		<p class="add-answer">
			<button class="button add-question-option-button" type="button"><?php esc_html_e( 'Add a new Answer', 'learnpress' ); ?></button>
		</p>
	<?php endif; ?>
</div>