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
			$result     = sprintf(
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
	<button type="button" class="button btn-remove-all" disabled data-confirmed = "<?php esc_attr_e( 'Are you sure to remove all the blanks?', 'learnpress' ); ?>"><?php esc_html_e( 'Remove all blanks', 'learnpress' ); ?></button>
	<button type="button" class="button btn-clear" data-confirmed = "<?php esc_attr_e( 'Are you sure to clear content?', 'learnpress' ); ?>" disabled><?php esc_html_e( 'Clear content', 'learnpress' ); ?></button>
</p>
<table class="fib-blanks">
	<?php learn_press_admin_view( 'question/fib-blank' ); ?>
</table>