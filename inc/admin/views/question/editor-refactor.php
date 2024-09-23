<?php
/**
 * Admin Question Editor: Editor template.
 *
 * @since 4.2.7
 * @author vuxminhthanh
 */

$data = wp_parse_args(
	$args,
	[
		'id' => get_the_ID() ?? '',
	]
);

$question_id         = $data['id'];
$types               = LP_Question::get_types();
$question            = LP_Question::get_question( $question_id );
$question_type_label = $question->get_type_label() ?? '';
$question_type_key   = $question->get_type() ?? '';

$get_data_answer = [
	'true_or_false'  => 'question/answer-refactor',
	'multi_choice'   => 'question/answer-refactor',
	'single_choice'  => 'question/answer-refactor',
	'fill_in_blanks' => 'question/fib-answer-editor-refactor',
];

apply_filters( 'learnpress/question/get_data_answer', $get_data_answer );
?>

<div class="lp-admin-editor learn-press-box-data fill_in_blanks js-admin-editor-lp_question" data-question-id = "<?php echo esc_attr( $question_id ); ?>">
	<div class="lp-box-data-head lp-row">
		<h3 class="heading"><?php esc_html_e( 'Details', 'learnpress' ); ?></h3>
		<div class="status"></div>
		<div class="lp-question-editor lp-question-editor--right">
			<div class="lp-question-editor__inner">
				<div class="question-types">
					<a data-type="<?php echo esc_attr( $question_type_key ); ?>"><?php echo esc_html( $question_type_label ); ?></a>
					<ul>
						<?php foreach ( $types as $key => $type ) : ?>
							<li data-type="<?php echo esc_attr( $key ); ?>" class=""><a href=""><?php echo esc_html( $type ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div id="lp-admin-question-editor" class="lp-admin-fib-question-editor">
		<div class="lp-box-data-content">
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
	</div>
</div>