<?php
global $post, $wp_meta_boxes;
$quiz = learn_press_get_quiz( $post );
?>
<div id="learn-press-quiz-questions" class="learn-press-box-data" ng-controller="quiz">
    <div class="lp-box-data-head">
        <h3><?php printf( __( '%d questions', 'learnpress' ), 10 ); ?></h3>
    </div>
    <div class="lp-box-data-content">
        <table class="lp-list-questions">
            <thead>
            <tr>
                <th class="column-sort"></th>
                <th class="column-order">#</th>
                <th class="column-name"><?php _e( 'Name', 'learnpress' ); ?></th>
                <th class="column-type"><?php _e( 'Type', 'learnpress' ); ?></th>
                <th class="column-actions"><?php _e( 'Actions', 'learnpress' ); ?></th>
            </tr>
            </thead>
			<?php
			$questions = $quiz->get_questions();
			$index     = 0;
			foreach ( $questions as $question_id ) {
				if ( $question = learn_press_get_question( $question_id ) ) {
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
							'answer_options' => array(),
							'icon-class'     => ''
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
					//$top_buttons['move']   = sprintf( '<span class="lp-toolbar-btn lp-btn-move"><a data-tooltip="%s" class="lp-btn-icon dashicons dashicons-sort learn-press-tooltip"></a></span>', esc_attr__( 'Drag & drop to sort question', 'learnpress' ) );

					$top_buttons = apply_filters( 'learn_press_question_top_buttons', $top_buttons, $question_id );
					$top_buttons = array_filter( $top_buttons );
					$box_classes = array( 'learn-press-box-data learn-press-question lp-question-' . $template_data['type'] );
					if ( learn_press_is_hidden_post_box( $question_id ) ) {
						$box_classes[] = 'closed';
					}
					?>
                    <tbody ng-controller="question">
                    <tr>
                        <td class="column-sort"><i class="fa fa-bars"></i></td>
                        <td class="column-order"><?php echo ++ $index; ?></td>
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
				<?php } ?>
			<?php } ?>
        </table>
    </div>
</div>
