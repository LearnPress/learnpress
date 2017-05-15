<?php
//defined( 'ABSPATH' ) or exit();
$question = isset( $question ) ? $question : false;
if ( ! $question ) {
}
$question_id     = $question->get_id();
$type            = $question->get_type();
$option_headings = $question->get_admin_option_headings();
$questionOptions = array();

$dropdown      = LP_Question_Factory::list_question_types( array( 'selected' => $type, 'echo' => false, 'li_attr' => 'ng-class="{active: questionData.type==\'{{type}}\'}"' ) );
$template_data = array_merge(
	array(
		'id'             => $question_id,
		'type'           => $type,
		'title'          => $question->get_title(),
		'answer_options' => array(
			'value'   => $value,
			'text'    => $answer['text'],
			'is_true' => $answer['is_true']
		)
	),
	$question->get_option_template_data()
);
?>
<div class="learn-press-box-data learn-press-question lp-question-<?php echo $template_data['type']; ?>"
     id="learn-press-question-<?php echo $template_data['id']; ?>"
     data-type="<?php echo $type; ?>" data-id="<?php echo $template_data['id']; ?>"
     ng-controller="question">
    <div class="lp-box-data-head lp-row">
        <div class="lp-box-data-actions lp-toolbar-buttons">
			<?php
			$top_buttons = apply_filters(
				'learn_press_question_top_buttons',
				array(
					'type'   => sprintf( '<div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type"><a href="" class="lp-btn-icon dashicons dashicons-editor-help"></a>%s</div>', $dropdown ),
					'edit'   => LP_QUESTION_CPT == get_post_type() ? '' : '<div class="lp-toolbar-btn" ng-class="{\'lp-btn-disabled\': !questionData.id}"><a target="_blank" href="post.php?post={{questionData.id}}&action=edit" class="lp-btn-icon dashicons dashicons-admin-links"></a></div>',
					'remove' => '<span class="lp-toolbar-btn lp-btn-toggle" ng-click="toggleContent($event)" ><a class="lp-btn-icon dashicons dashicons-arrow-up"></a><a href="" class="lp-btn-icon dashicons dashicons-arrow-down"></a></span>',
					'toggle' => '<span class="lp-toolbar-btn lp-btn-remove "><a class="lp-btn-icon dashicons dashicons-trash"></a></span>',
					'move'   => '<span class="lp-toolbar-btn lp-btn-move"><a href="" class="lp-btn-icon dashicons dashicons-sort"></a></span>'
				),
				$question_id
			);
			$top_buttons = array_filter( $top_buttons );
			echo join( "<!--\n-->", $top_buttons );
			?>
        </div>
		<?php if ( LP_QUESTION_CPT !== get_post_type() ) { ?>
            <input type="text" class="lp-question-heading-title" value="<?php echo $template_data['title']; ?>">
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