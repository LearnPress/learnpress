<?php
defined( 'ABSPATH' ) or exit();
$question        = isset( $question ) ? $question : exit();
$question_id     = $question->get_id();
$option_headings = $question->get_admin_option_headings();
$questionOptions = array();
?>
<div class="learn-press-box-data learn-press-question" id="learn-press-question-<?php echo $question_id; ?>"
     data-type="multi-choice" data-id="<?php echo $question_id; ?>"
     ng-controller="question">
	<?php do_action( 'learn-press/question/multi-choices/admin-js-template' ); ?>
    <div class="lp-box-data-head">
        <p class="question-bottom-actions">
            <span><?php _e( 'Question data', 'learnpress' ); ?> &mdash;</span>
			<?php
			$top_buttons = apply_filters(
				'learn_press_question_top_buttons',
				array(
					'change_type' => learn_press_dropdown_question_types( array(
						'echo'     => false,
						'id'       => 'learn-press-dropdown-question-types-' . $question_id,
						'selected' => $question->type
					) )
				),
				$question_id
			);
			echo join( "\n", $top_buttons );
			?>
        </p>
	    <?php if ( LP_QUESTION_CPT !== get_post_type() ) { ?>
            <input type="text" class="lp-question-heading-title" value="<?php echo $question->get_title(); ?>">
	    <?php } ?>
    </div>
    <div class="lp-box-data-content">
        <table class="lp-sortable lp-list-options" id="learn-press-list-options-<?php echo $question_id; ?>">
            <thead>
            <tr>
				<?php foreach ( $option_headings as $key => $text ) { ?>
					<?php
					$classes = apply_filters( 'learn-press/question/multi-choices/admin-option-column-heading-class', array(
						'column-heading',
						'column-heading-' . $key
					) );
					?>
                    <th class="<?php echo join( ' ', $classes ); ?>">
						<?php do_action( 'learn-press/question/multi-choices/admin-option-column-heading-before-title', $key, $question_id ); ?>
						<?php echo apply_filters( 'learn-press/question/multi-choices/admin-option-column-heading-title', $text ); ?>
						<?php do_action( 'learn-press/question/multi-choices/admin-option-column-heading-after-title', $key, $question_id ); ?>
                    </th>
				<?php } ?>
            </tr>
            </thead>
            <tbody>
			<?php
			$answers = $question->answers;
			if ( $answers ):
				foreach ( $answers as $answer ):
					ob_start();
					learn_press_admin_view( 'meta-boxes/question/multi-choice-option', array(
						'question' => $question,
						'answer'   => $answer
					) );
					echo $questionOption = ob_get_clean();
					$key    = $question->get_option_value( $answer['value'] );
					$option = array( 'html' => $questionOption, 'attr' => array() );
					if ( preg_match_all( '~<tr(.*)>~iSU', $questionOption, $matches ) ) {
						if ( preg_match_all( '~(.*)="(.*)"~iSU', $matches[1][0], $attrs ) ) {
							foreach ( $attrs[1] as $k => $v ) {
								$option['attr'][ trim( $v ) ] = $attrs[2][ $k ];
							}
						}
					}
					$questionOptions[ $key ] = $option;
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
						$question_id,
						__( 'Add Option', 'learnpress' )
					)
				),
				$question_id
			);
			echo join( "\n", $bottom_buttons );
			?>
        </p>
    </div>
</div>