<?php
/**
 * Template for displaying generic question header interface.
 */
defined( 'ABSPATH' ) or exit();

$question        = isset( $question ) ? $question : false;
$question_id     = $question->get_id();
$type            = $question->get_type();
$questionOptions = array();
$dropdown        = LP_Question_Factory::list_question_types( array(
	'selected' => $type,
	'echo'     => false,
	'li_attr'  => 'ng-class="{active: questionData.type==\'{{type}}\'}"'
) );
$template_data   = array_merge(
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
$top_buttons           = array();
$top_buttons['type']   = learn_press_admin_view_content( 'question/html-button-type', array( 'type' => $type ) );
$top_buttons['clone']  = learn_press_admin_view_content( 'question/html-button-clone' );
$top_buttons['toggle'] = learn_press_admin_view_content( 'question/html-button-toggle' );

// Filter
$top_buttons = apply_filters( 'learn-press/question/top-buttons', $top_buttons, $question_id );

// Remove empty values
$top_buttons = array_filter( $top_buttons );

$box_classes = array( 'learn-press-box-data learn-press-question lp-question-' . $template_data['type'] );
if ( learn_press_is_hidden_post_box( $question_id ) ) {
	$box_classes[] = 'closed';
}
?>
<div class="<?php echo join( ' ', $box_classes ); ?>"
     id="learn-press-question-<?php echo $template_data['id']; ?>"
     data-type="<?php echo $type; ?>"
     data-dbid="<?php echo $template_data['id']; ?>"
     ng-controller="question"
     ng-click="elementClick($event)"
     ng-class="{'invalid-type': !isValidQuestionType()}">
    <div class="lp-box-data-head lp-row">
        <h3><?php _e( 'Answer options', 'learnpress' ); ?></h3>
        <div class="lp-box-data-actions lp-toolbar-buttons">
			<?php
			echo join( "<!--\n-->", $top_buttons );
			?>
        </div>

    </div>
    <div class="lp-box-data-content">