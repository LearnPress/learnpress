<?php
/**
 * Template for displaying question answer options in admin.
 * Base question types: True or False, Single Choice, Multi Choices.
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Admin
 * @version 3.0
 */

defined( 'ABSPATH' ) || exit();
$option_headings = $question->get_admin_option_headings();

?>
    <table class="lp-sortable lp-list-options" id="learn-press-list-options-<?php echo $template_data['id']; ?>">
        <thead>
        <tr>
			<?php foreach ( $option_headings as $key => $text ) { ?>
				<?php
				$classes = apply_filters( "learn-press/question/{$type}/admin-option-column-heading-class", array(
					'column-heading',
					'column-heading-' . $key
				) );
				?>
                <th class="<?php echo join( ' ', $classes ); ?>">
					<?php do_action( "learn-press/question/{$type}/admin-option-column-heading-before-title", $key, $template_data['id'] ); ?>
					<?php echo apply_filters( "learn-press/question/{$type}/admin-option-column-heading-title", $text ); ?>
					<?php do_action( "learn-press/question/{$type}/admin-option-column-heading-after-title", $key, $template_data['id'] ); ?>
                </th>
			<?php } ?>
        </tr>
        </thead>
        <tbody>
		<?php
		$answers = $question->get_answer_options();
		if ( $answers ):
			foreach ( $answers as $answer ):
				ob_start();
				learn_press_admin_view( 'meta-boxes/question/base-option', array(
					'question' => $question,
					'answer'   => $answer
				) );
				echo $questionOption = ob_get_clean();
			endforeach;
		endif;
		?>
        </tbody>
    </table>
<?php
$bottom_buttons = array();
if ( $question->is_support( 'add-answer-option' ) ) {
	$bottom_buttons['add_option'] = sprintf(
		__( '<button class="button add-question-option-button add-question-option-button-%1$d" data-id="%1$d" type="button" ng-click="addOption()">%2$s</button>', 'learnpress' ),
		$template_data['id'],
		__( 'Add Option', 'learnpress' )
	);
}
$bottom_buttons = apply_filters(
	'learn_press_question_bottom_buttons',
	$bottom_buttons,
	$template_data['id']
);
if ( $bottom_buttons ) {
	printf( '<p class="lp-box-data-foot question-bottom-actions">%s</p>', join( "\n", $bottom_buttons ) );
}
?>