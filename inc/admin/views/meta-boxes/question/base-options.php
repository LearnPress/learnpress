<?php
//defined( 'ABSPATH' ) or exit();
$question = isset( $question ) ? $question : false;
if ( ! $question ) {
}
$question_id         = $question->get_id();
$type                = $question->get_type();
$option_headings     = $question->get_admin_option_headings();
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
$top_buttons['type'] = sprintf( '
    <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
        <a data-tooltip="%s" class="lp-btn-icon dashicons dashicons-editor-help"></a>
        %s
     </div>',
	esc_attr__( 'Change type of this question', 'learnpress' ),
	$dropdown
);
if ( LP_QUESTION_CPT != get_post_type() ) {
	$top_buttons['edit'] = sprintf( '
        <div class="lp-toolbar-btn" ng-class="{\'lp-btn-disabled\': !questionData.id}">
            <a target="_blank" data-tooltip="%s" href="post.php?post={{questionData.id}}&action=edit" class="lp-btn-icon dashicons dashicons-admin-links learn-press-tooltip"></a>
        </div>',
		esc_attr__( 'Edit question in new window', 'learnpress' )
	);
}
$top_buttons['remove'] = sprintf( '
    <span class="lp-toolbar-btn lp-btn-toggle learn-press-tooltip" data-tooltip="%s" ng-click="toggleContent($event)" >
        <a class="lp-btn-icon dashicons dashicons-arrow-up"></a>
        <a class="lp-btn-icon dashicons dashicons-arrow-down"></a>
    </span>',
	esc_attr__( 'Toggle question content', 'learnpress' )
);

$top_buttons['toggle'] = sprintf( '
    <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
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
?>
<div class="learn-press-box-data learn-press-question closed lp-question-<?php echo $template_data['type']; ?>"
     id="learn-press-question-<?php echo $template_data['id']; ?>"
     data-type="<?php echo $type; ?>" data-id="<?php echo $template_data['id']; ?>"
     ng-controller="question"
     ng-click="elementClick($event)">
    <div class="lp-box-data-head lp-row">
        <div class="lp-box-data-actions lp-toolbar-buttons">
			<?php
			echo join( "<!--\n-->", $top_buttons );
			?>
        </div>
		<?php if ( LP_QUESTION_CPT !== get_post_type() ) { ?>
            <input type="text" class="lp-question-heading-title"
                   value="<?php echo esc_attr( $template_data['title'] ); ?>"
                   autocomplete="off"
                   ng-keypress="onQuestionKeyEvent($event)"
                   ng-keyup="onQuestionKeyEvent($event)"
                   ng-keydown="onQuestionKeyEvent($event)"
                   ng-change="update($event)" ng-model="questionData.title">
		<?php } ?>
    </div>
    <div class="lp-box-data-content">
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
            <!--
			<tr ng-repeat="option in questionOptions track by $index" content-rendered="updateOption">
				<div ng-include="tmpl-question-multi-choice-option"></div>
			</tr>-->

            </tbody>
        </table>
        <p class="lp-box-data-foot question-bottom-actions">
			<?php
			$bottom_buttons = apply_filters(
				'learn_press_question_bottom_buttons',
				array(
					'add_option' => sprintf(
						__( '<button class="button add-question-option-button add-question-option-button-%1$d" data-id="%1$d" type="button" ng-click="addOption()">%2$s</button>', 'learnpress' ),
						$template_data['id'],
						__( 'Add Option', 'learnpress' )
					)
				),
				$template_data['id']
			);
			echo join( "\n", $bottom_buttons );
			?>
        </p>
    </div>
    <input type="hidden" class="question-id" value="<?php echo $template_data['id']; ?>">
    <input type="hidden" class="question-type" value="<?php echo $template_data['type']; ?>">

    <div class="hide-if-js element-data">
		<?php $question->to_element_data(); ?>
    </div>
</div>