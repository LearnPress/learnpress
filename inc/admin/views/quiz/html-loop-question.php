<?php
$question_id     = $question->get_id();
$type            = $question->get_type();
$questionOptions = array();

$template_data = array_merge(
	array(
		'id'             => $question_id,
		'type'           => $type,
		'title'          => $question->get_title(),
		'answer_options' => array(),
		'icon-class'     => ''
	),
	$question->get_option_template_data()
);

// Get question actions
$top_buttons         = array();
$top_buttons['type'] = learn_press_admin_view_content( 'quiz/html-button-type', array( 'type' => $type ) );
if ( LP_QUESTION_CPT != get_post_type() ) {
	$top_buttons['edit']  = learn_press_admin_view_content( 'quiz/html-button-edit' );
	$top_buttons['clone'] = learn_press_admin_view_content( 'quiz/html-button-clone' );
}
$top_buttons['remove'] = learn_press_admin_view_content( 'quiz/html-button-remove' );
$top_buttons['toggle'] = learn_press_admin_view_content( 'quiz/html-button-toggle' );
$top_buttons           = apply_filters( 'learn_press_question_top_buttons', $top_buttons, $question_id );
$top_buttons           = array_filter( $top_buttons );

$box_classes = array( 'learn-press-box-data learn-press-question lp-question-' . $template_data['type'] );
if ( learn_press_is_hidden_post_box( $question_id ) ) {
	$box_classes[] = 'closed';
}
?>
<tbody
       id="learn-press-question-<?php echo $template_data['id']; ?>"
       class="learn-press-question"
       data-type="<?php echo $type; ?>"
       data-dbid="<?php echo $template_data['id']; ?>"
       ng-controller="question"
       ng-click="elementClick($event)"
       ng-class="{'invalid-type': !isValidQuestionType()}">
<tr>
    <td class="column-sort"><i class="fa fa-bars"></i></td>
    <td class="column-order">{{getQuestionIndex(this)}}</td>
    <td class="column-name">
        <input type="text" class="lp-question-heading-title"
               value="<?php echo esc_attr( $template_data['title'] ); ?>"
               name="learn_press_question[<?php echo $template_data['id']; ?>][title]"
               autocomplete="off"
               ng-keypress="onQuestionKeyEvent($event)"
               ng-keyup="onQuestionKeyEvent($event)"
               ng-keydown="onQuestionKeyEvent($event)"
               ng-blur="onQuestionKeyEvent($event)">
    </td>
    <td class="column-type"><?php echo $question->get_type_label(); ?></td>
    <td class="column-actions">
        <div class="lp-box-data-actions lp-toolbar-buttons">
			<?php
			echo join( "<!--\n-->", $top_buttons );
			?>
        </div>
    </td>
</tr>
<tr class="edit-inline hide-if-js">
    <td colspan="5">
        <div class="quiz-question-data">
			<?php
			include $question->get_view();
			?>
        </div>
        <div class="quiz-question-options">
			<?php
			$question->output_meta_box_settings();
			?>
        </div>
    </td>
</tr>
</tbody>