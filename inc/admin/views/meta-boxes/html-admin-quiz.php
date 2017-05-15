<?php
global $post;
?>
<div ng-controller="quiz">
		<?php
		$questions = array( 5072, 5162 );
		foreach ( $questions as $question_id ) {
			$question = LP_Question_Factory::get_question( $question_id );
			$question->admin_interface();
		}
		?>
    <button class="button" type="button"
            ng-click="addQuestion($event);"><?php _e( 'Add Question', 'learnprress' ); ?></button>
    <div ng-repeat="question in questions track by $index">
        xxxx
    </div>
    {{quizData}}
    <script type="text/html" class="quiz-element-data">
		<?php echo json_encode( array( 'id' => $post->ID ) ); ?>
    </script>
</div>
<script type="text/javascript">
    var quizQuestions = <?php echo json_encode( $questions );?>;
</script>
