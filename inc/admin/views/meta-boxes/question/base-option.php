<?php
/**
 * Admin template for displaying multi choice option
 *
 * @package LearnPress/Templates/Admin
 */
///defined( 'ABSPATH' ) or exit();
$question = isset( $question ) ? $question : false;

if ( ! $question ) {
}

$type            = $question->get_type();
$option_headings = $question->get_admin_option_headings();
$value           = $question->get_option_value( $answer['value'] );
$id              = $question->get_id();
$input_type      = $type == 'multi_choice' ? 'checkbox' : 'radio';
do_action( 'learn_press_before_question_answer_option', $id );
$template_data = array_merge(
	array(
		'id'             => $question->get_id(),
		'answer_options' => array(
			'value'   => $value,
			'text'    => $answer['text'],
			'is_true' => $answer['is_true']
		)
	),
	$question->get_option_template_data()
);
?>

    <tr class="lp-list-option lp-row lp-list-option-<?php echo $template_data['answer_value']; ?>"
        data-id="<?php echo $template_data['answer_value']; ?>">
		<?php foreach ( $option_headings as $heading => $title ) { ?>
			<?php
			$classes = array( 'column-content', 'column-content-' . $heading );
			$tooltip = '';
			ob_start();
			switch ( $heading ) {
				case 'answer_text':
					?>
                    <input class="lp-answer-text no-submit key-nav" type="text"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][answer_options][text][]"
                           value="<?php echo esc_attr( $template_data['answer_options']['text'] ); ?>"
                           placeholder="<?php esc_attr_e( 'Type name of option', 'learnpress' ); ?>"
                           autocomplete="off"
                           ng-keypress="onOptionKeyEvent($event)"
                           ng-keyup="onOptionKeyEvent($event)"
                           ng-keydown="onOptionKeyEvent($event)"
                    />
					<?php
					break;
				case 'answer_correct':
					$classes[] = 'lp-answer-check';
					?>
                    <input type="hidden"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][answer_options][value][]"
                           value="<?php echo $template_data['answer_options']['value']; ?>"/>
                    <input type="<?php echo $input_type; ?>"
                           name="learn_press_question[<?php echo $template_data['id']; ?>][answer_options][checked][]"
						<?php checked( $template_data['answer_options']['is_true'] == 'yes', true ); ?>
                           value="<?php echo $template_data['answer_options']['value']; ?>"
                    />
					<?php
					break;
				case 'actions':
					$classes[] = 'lp-toolbar-buttons';
					?>
                    <span class="learn-press-tooltip lp-toolbar-btn lp-btn-remove"
                          data-tooltip="<?php esc_attr_e( 'Remove this option', 'learnpress' ); ?>"
                          ng-click="removeOption($event)">
                        <a class="lp-btn-icon dashicons dashicons-trash"></a>
                    </span><!--
                    --><span class="learn-press-tooltip lp-toolbar-btn lp-btn-move"
                             data-tooltip="<?php esc_attr_e( 'Drag and drop to change answer\'s position', 'learnpress' ); ?>">
                        <a class="lp-btn-icon dashicons dashicons-sort"></a>
                    </span>

					<?php
					break;
			}
			if ( $tooltip ) {
				$classes[] = 'learn-press-tooltip';
			}
			$classes = apply_filters( "learn-press/question/{$type}/admin-option-column-class", $classes, $heading, $answer, $template_data, $id );
			$classes = array_filter( $classes );
			$classes = array_unique( $classes );
			?>
			<?php do_action( "learn-press/question/{$type}/admin-option-column-" . $heading . '-content', $answer, $template_data, $id ); ?>
			<?php do_action( "learn-press/question/{$type}/admin-option-columns-content", $heading, $answer, $template_data, $id ); ?>
			<?php $html = ob_get_clean(); ?>
            <td class="<?php echo join( ' ', $classes ); ?>"<?php if ( $tooltip ) {
				echo ' data-tooltip="' . $tooltip . '"';
			} ?>>
				<?php echo $html; ?>
            </td>
		<?php } ?>
    </tr>
<?php do_action( 'learn_press_after_question_answer_option', $id ); ?>