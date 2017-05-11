<?php
/**
 * Admin template for displaying multi choice option
 *
 * @package LearnPress/Templates/Admin
 */
defined( 'ABSPATH' ) or exit();
$question        = isset( $question ) ? $question : exit();
$option_headings = $question->get_admin_option_headings();
$value           = $question->get_option_value( $answer['value'] );
$id              = $question->get_id();

do_action( 'learn_press_before_question_answer_option', $id );

$template_data = array_merge(
	array(
		'id'           => $question->get_id(),
		'answer_value' => $value,
		'answer_text'  => $answer['text']
	),
	$question->get_option_template_data()
);
?>

    <tr class="lp-list-option lp-list-option-<?php echo $template_data['answer_value']; ?>"
        data-id="<?php echo $template_data['answer_value']; ?>">
		<?php foreach ( $option_headings as $heading => $title ) { ?>
			<?php
			$classes         = array( 'column-content', 'column-content-' . $heading );
			$tooltip         = '';
			ob_start();
			switch ( $heading ) {
				case 'answer_text':
					?>
                    <input class="lp-answer-text no-submit key-nav" type="text"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][answer][text][]"
                           value="<?php echo esc_attr( $template_data['answer_text'] ); ?>"
                           placeholder="<?php esc_attr_e( 'Type name of option', 'learnpress' ); ?>"
                    />
					<?php
					break;
				case 'answer_correct':
					$classes[] = 'lp-answer-check';
					?>
                    <input type="hidden"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][answer][value][]"
                           value="<?php echo $template_data['answer_value']; ?>"/>
                    <input type="checkbox"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][checked][]" <?php checked( $answer['is_true'] == 'yes', true ); ?>
                           value="<?php echo $template_data['answer_value']; ?>"
                           ng-model="option.answer_correct"
                    />
					<?php
					break;
				case 'actions':
					$classes[] = 'lp-list-option-actions lp-remove-list-option';
					$tooltip = learn_press_sanitize_tooltip( __( 'Remove this answer', 'learnpress' ) );
					?>
                    <a class="dashicons dashicons-trash"></a>
					<?php
					break;
				case 'sort':
					$classes[] = 'lp-list-option-actions lp-move-list-option open-hand';
					$tooltip = learn_press_sanitize_tooltip( __( 'Drag and drop to change answer\'s position', 'learnpress' ) );
					?>
                    <a class="dashicons dashicons-sort"></a>
					<?php
					break;

			}
			if ( $tooltip ) {
				$classes[] = 'learn-press-tooltip';
			}
			$classes = apply_filters( 'learn-press/question/multi-choices/admin-option-column-class', $classes, $heading, $answer, $template_data, $id );
			$classes = array_filter( $classes );
			$classes = array_unique( $classes );
			?>
			<?php do_action( 'learn-press/question/multi-choices/admin-option-column-' . $heading . '-content', $answer, $template_data, $id ); ?>
			<?php do_action( 'learn-press/question/multi-choices/admin-option-columns-content', $heading, $answer, $template_data, $id ); ?>
			<?php $html = ob_get_clean(); ?>
            <td class="<?php echo join( ' ', $classes ); ?>"<?php if ( $tooltip ) {
				echo ' data-tooltip="' . $tooltip . '"';
			} ?>>
				<?php echo $html; ?>
            </td>
		<?php } ?>
    </tr>
<?php do_action( 'learn_press_after_question_answer_option', $id ); ?>