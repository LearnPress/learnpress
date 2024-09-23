<?php
/**
 * Admin Answer Fib item: Editor template.
 *
 * @since 4.2.7
 * @author VuxMinhThanh
 */

$data = wp_parse_args(
	$args,
	[
		'id'      => '',
		'open'    => false,
		'title'   => 'New Answer',
		'type'    => array(),
		'answers' => array(),
	]
);

function format_fib_data( $content ) {
	$pattern = '/\[fib fill="(.*?)" id="(.*?)" comparison="(.*?)" match_case="(.*?)" open="(.*?)"\]/';
	$index   = 1;

	$formattedContent = preg_replace_callback(
		$pattern,
		function ( $matches ) use ( &$index ) {
			$fill       = $matches[1];
			$id         = $matches[2];
			$comparison = $matches[3];
			$matchCase  = $matches[4] == '1' ? 'true' : 'false';
			$open       = $matches[5] == '1' ? 'true' : 'false';
			$result = sprintf(
				'<b class="fib-blank" id="fib-blank-%s" data-id="%s" data-comparison="%s" data-match-case="%s" data-open="%s" data-index="%d">%s</b>',
				htmlspecialchars( $id ),
				htmlspecialchars( $id ),
				htmlspecialchars( $comparison ),
				htmlspecialchars( $matchCase ),
				htmlspecialchars( $open ),
				$index,
				htmlspecialchars( $fill )
			);

			$index++;
			return $result;
		},
		$content
	);

	return $formattedContent;
}

$answers     = $data['answers'][0];
$html_title  = format_fib_data( $answers['title'] );
$comparisons = [
	[
		'value'       => 'equal',
		'label'       => esc_html( 'Equal', 'learnpress' ),
		'description' => __( 'Match two words are equality.', 'learnpress' ),
	],
	[
		'value'       => 'range',
		'label'       => esc_html( 'Range', 'learnpress' ),
		'description' => __( 'Match any number in a range. Use <code>100, 200</code> to match any value from 100 to 200.', 'learnpress' ),
	],
	[
		'value'       => 'any',
		'label'       => esc_html( 'Any', 'learnpress' ),
		'description' => __( 'Match any value in a set of words. Use <code>fill, blank, or question</code> to match any value in the set.', 'learnpress' ),
	],
];
?>
<div contenteditable="true" class="content-editable" data-is-true="<?php echo esc_attr( $answers['is_true'] ); ?>" data-value="<?php echo esc_attr( $answers['value'] ); ?>" data-order="<?php echo esc_attr( $answers['order'] ); ?>" data-answer-id="<?php echo esc_attr( $answers['question_answer_id'] ); ?>">
	<?php echo ( $html_title ); ?>
</div>
<div class="description">
	<p>
		<?php _e( 'Select a word in the passage above and click <strong>\'Insert a new blank\'</strong> to make that word a blank for filling.', 'learnpress' ); ?>
	</p>
</div>
<p class="action-fib-answer">
	<button type="button" class="button btn-add-new" disabled><?php esc_html_e( 'Insert a new blank', 'learnpress' ); ?></button>
	<button type="button" class="button btn-remove-all" disabled><?php esc_html_e( 'Remove all blanks', 'learnpress' ); ?></button>
	<button type="button" class="button btn-clear" disabled><?php esc_html_e( 'Clear content', 'learnpress' ); ?></button>
</p>
<table class="fib-blanks">
	<tbody class="fib-blank" style="display:none;">
		<tr>
			<td class="blank-position" width="50"></td>
			<td class="blank-fill">
				<input type="text">
			</td>
			<td class="blank-actions">
				<span class="blank-status"></span>
				<a class="option button"><?php esc_html_e( 'Options', 'learnpress' ); ?></a>
				<a class="delete button"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a>
			</td>
		</tr>
		<tr class="blank-options">
			<td width="50"></td>
			<td colspan="2">
				<ul>
					<li>
						<label>
							<input type="checkbox">
							<?php esc_html_e( 'Match case', 'learnpress' ); ?></label>
						<p class="description"><?php esc_html_e( 'Match two words in case sensitive.', 'learnpress' ); ?></p>
					</li>
					<li>
						<h4><?php esc_html_e( 'Comparison', 'learnpress' ); ?></h4>
					</li>
					<?php foreach ( $comparisons as $comparison ) : ?>
						<li>
							<label>
								<input type="radio" value="<?php echo esc_attr( $comparison['value'] ); ?>">
								<?php echo $comparison['label']; ?>
							</label>
							<p class="description">
								<?php echo $comparison['description']; ?>
							</p>
						</li>
					<?php endforeach; ?>
				</ul>
			</td>
		</tr>
	</tbody>
</table>