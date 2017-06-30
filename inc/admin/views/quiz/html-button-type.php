<div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type" ng-click="changeQuestionType($event)">
    <a data-tooltip="<?php esc_attr_e( 'Change type of this question', 'learnpress' ); ?>"
       class="lp-btn-icon dashicons dashicons-editor-help"></a>
	<?php
	LP_Question_Factory::list_question_types( array(
		'selected' => $type,
		'li_attr'  => 'ng-class="{active: questionData.type==\'{{type}}\'}"'
	) );
	?>
</div>