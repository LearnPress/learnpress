<?php
/**
 * Template for displaying generic question header interface.
 */
defined( 'ABSPATH' ) or exit();

$question            = isset( $question ) ? $question : false;
$question_id         = $question->get_id();
$type                = $question->get_type();
$questionOptions     = array();
$dropdown            = LP_Question_Factory::list_question_types( array(
	'selected' => $type,
	'echo'     => false,
	'li_attr'  => 'ng-class="{active: questionData.type==\'{{type}}\'}"'
) );
$template_data       = array_merge(
	array(
		'id'             => $question_id,
		'type'           => $type,
		'title'          => $question->get_title(),
		'answer_options' => array()
	),
	$question->get_option_template_data()
);
$top_buttons         = array();
$top_buttons['type'] = sprintf( '<div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type" ng-click="changeQuestionType($event)">
        <a data-tooltip="%s" class="lp-btn-icon dashicons dashicons-editor-help"></a>
        %s
     </div>',
	esc_attr__( 'Change type of this question', 'learnpress' ),
	$dropdown
);
if ( LP_QUESTION_CPT != get_post_type() ) {
	$top_buttons['edit'] = sprintf( '<div class="lp-toolbar-btn" ng-show="isSaved()">
            <a target="_blank" data-tooltip="%s" href="post.php?post={{questionData.id}}&action=edit" class="lp-btn-icon dashicons dashicons-admin-links learn-press-tooltip"></a>
        </div>',
		esc_attr__( 'Edit question in new window', 'learnpress' )
	);

	$top_buttons['clone'] = sprintf( '<div class="lp-toolbar-btn" ng-class="{\'lp-btn-disabled\': !questionData.id}">
            <a target="_blank" data-tooltip="%s" ng-click="cloneQuestion($event)" class="lp-btn-icon dashicons dashicons-admin-page learn-press-tooltip"></a>
        </div>',
		esc_attr__( 'Clone this question', 'learnpress' )
	);
}

$top_buttons['remove'] = sprintf( '<span class="lp-toolbar-btn lp-btn-toggle learn-press-tooltip" data-tooltip="%s" ng-click="toggleContent($event)" >
        <a class="lp-btn-icon dashicons dashicons-arrow-up-alt2"></a>
        <a class="lp-btn-icon dashicons dashicons-arrow-down-alt2"></a>
    </span>',
	esc_attr__( 'Toggle question content', 'learnpress' )
);

$top_buttons['toggle'] = sprintf( '<div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
        <a data-tooltip="%s" class="lp-btn-icon dashicons dashicons-trash learn-press-tooltip" ng-click="removeQuestion($event)"></a>
        <ul>
            <li><a class="learn-press-tooltip" data-tooltip="%s" ng-click="removeQuestion($event)" data-delete-permanently="yes">%s</a></li>
        </ul>
    </div>',
	esc_attr__( 'Remove this question', 'learnpress' ),
	esc_attr__( 'Delete permanently this question from Questions Bank', 'learnpress' ),
	esc_attr__( 'Delete permanently', 'learnpress' )
);
$top_buttons['move']   = sprintf( '<span class="lp-toolbar-btn lp-btn-move"><a data-tooltip="%s" class="lp-btn-icon dashicons dashicons-sort learn-press-tooltip"></a></span>', esc_attr__( 'Drag & drop to sort question', 'learnpress' ) );

$top_buttons = apply_filters( 'learn_press_question_top_buttons', $top_buttons, $question_id );
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
        <span class="lp-item-counter" data-count="{{getPosition()}}"></span>
        <span class="lp-item-icon-type dashicons"></span>
        <div class="lp-box-data-actions lp-toolbar-buttons">
			<?php
			echo join( "<!--\n-->", $top_buttons );
			?>
        </div>
		<?php if ( LP_QUESTION_CPT !== get_post_type() ) { ?>
            <input type="text" class="lp-question-heading-title"
                   value="<?php echo esc_attr( $template_data['title'] ); ?>"
                   name="learn_press_question[<?php echo $template_data['id']; ?>][title]"
                   autocomplete="off"
                   ng-keypress="onQuestionKeyEvent($event)"
                   ng-keyup="onQuestionKeyEvent($event)"
                   ng-keydown="onQuestionKeyEvent($event)"
                   ng-blur="onQuestionKeyEvent($event)">
		<?php } ?>
    </div>
    <div class="lp-box-data-content">