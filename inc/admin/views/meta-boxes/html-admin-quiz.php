<?php
global $post;
?>
<div ng-controller="quiz">
    <div id="learn-press-quiz-questions">
		<?php
		$questions = array( 5072, 5162 );
		foreach ( $questions as $question_id ) {
			$question = LP_Question_Factory::get_question( $question_id );
			$question->admin_interface();
		}
		?>
        <dynamic-page name='vaibhav'></dynamic-page>
    </div>
    <div class="lp-toolbar-buttons">
        <div class="button lp-group-button">
            <button class="button" type="button"
                    ng-click="addQuestion($event);"><?php _e( 'Add Question', 'learnprress' ); ?></button>
            <div class="lp-toolbar-btn lp-toolbar-btn-dropdown" id="learn-press-button-add-question">
                <button class="button" type="button"
                        ng-click="addQuestion($event);"><?php _e( '+', 'learnprress' ); ?></button>
				<?php
				LP_Question_Factory::list_question_types( array( 'li_attr' => 'ng-click="addQuestion($event, {type: \'{{type}}\'})"' ) );
				?>
            </div>
        </div>
    </div>
    <script type="text/html" class="quiz-element-data">
		<?php echo json_encode( array( 'id' => $post->ID ) ); ?>
    </script>
</div>
<script type="text/javascript">
    var quizQuestions = <?php echo json_encode( $questions );?>;
</script>
