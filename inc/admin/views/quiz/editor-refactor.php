<?php
/**
 * Admin Quiz Editor: Editor template.
 *
 * @since 4.2.7
 * @author VuxMinhThanh
 */

$quiz_id        = get_the_ID();
$quiz           = LP_Quiz::get_quiz( $quiz_id );
$question_ids   = $quiz->get_question_ids();
$get_mark       = $quiz->get_mark();
$label_question = $get_mark < 2 ? esc_html__( 'Question', 'learnpress' ) : esc_html__( 'Questions', 'learnpress' );
$types          = LP_Question::get_types();
?>

<div id="admin-editor-lp_quiz-refactor">
	<div class="lp-box-data-head heading">
		<h3><?php echo esc_html__( 'Details', 'learnpress' ); ?><span class="status"></span></h3>
		<div class="section-item-counts">
			<span>
				<?php echo sprintf( '%s %s', $get_mark, $label_question ); ?>
			</span>
		</div>
		<span class="collapse-list-questions dashicons dashicons-arrow-down"></span>
	</div>
	<div class="lp-box-data-content">
		<div class="lp-list-questions">
			<div class="main ui-sortable">
				<?php
				foreach ( $question_ids as $index => $id ) :
					$title = get_the_title( $id );
					learn_press_admin_view(
						'quiz/question-item',
						[
							'id'    => $id,
							'title' => $title,
							'order' => $index + 1,
						]
					);
				endforeach;
				?>
			</div>
			<div class="footer">
				<div class="table-row">
					<div class="sort lp-sortable-handle"></div>
					<div class="order"></div>
					<div class="name add-new-question">
						<div class="title">
							<input type="text" placeholder="<?php esc_attr_e( 'Create a new question', 'learnpress' ); ?>">
						</div>
						<div class="add-new-question__action">
							<div class="add-new">
								<button type="button" class="button" disabled>
									<?php esc_html_e( 'Choose question type', 'learnpress' ); ?>
								</button>
								<ul class="question-types">
									<?php foreach ( $types as $key => $type ) : ?>
										<li class="">
											<a  data-type="<?php echo esc_attr( $key ); ?>" href=""><?php echo esc_html( $type ); ?></a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
							<div class="select-item">
								<button type="button" class="button">
									<?php esc_html_e( 'Select items', 'learnpress' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$types = [
	'question' => esc_html__( 'Questions', 'learnpress' ),
];

learn_press_admin_view(
	'popup-select-item',
	[
		'types' => $types,
	]
); ?>