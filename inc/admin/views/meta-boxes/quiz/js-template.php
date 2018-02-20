<script type="text/html" id="tmpl-lp-modal-quiz-questions">
	<div id="lp-modal-quiz-questions" class="lp-modal-search">
		<div class="lp-search-items">
			<input type="text" name="lp-item-name" placeholder="<?php _e( 'Type here to search the questions', 'learnpress' );?>" />
			<!--<div class="button lp-button-dropdown lp-button-add-question">
				<span class="lp-dropdown-label lp-add-new-item"><?php _e( 'Add New', 'learnpress' );?></span>
				<span class="lp-dropdown-arrow">+</span>
				<ul class="lp-dropdown-items">
					<?php foreach( learn_press_question_types() as $slug => $name ){?>
						<li>
							<a href="" data-type="<?php echo $slug;?>"><?php echo $name;?></a>
						</li>
					<?php } ?>
				</ul>
			</div>-->
		</div>
		<ul class="lp-list-items">
			<?php
			$questions = learn_press_get_user_questions( null, array( 'posts_per_page' => 9999 ) );
			if ( $questions ) {
				foreach( $questions as $question ){
					$hidden = '';//in_array( $questions->ID, $exclude_lessons ) ? 'selected hide-if-js' : '';

					if( $question->post_title ){
						$title = $question->post_title;
					}else{
						$title = __( '(Untitled)', 'learnpress' );
					}
					?>
					<li class="<?php echo $hidden;?>" data-id="<?php echo $question->ID;?>" data-type="<?php echo $question->post_type;?>" data-text="<?php echo esc_attr( $title );?>">
						<label>
							<input type="checkbox" value="<?php echo $question->ID;?>">
							<span class="lp-item-text"><?php echo $title;?></span>
						</label>
					</li>
					<?php
				}
			}
			?>
			<li class="lp-search-no-results hide-if-js" data-id="0"><?php _e( 'No results', 'learnpress' );?></li>
		</ul>
		<button class="lp-add-item button" disabled="disabled" data-text="<?php _e( 'Add to quiz', 'learnpress' );?>"><?php _e( 'Add to quiz', 'learnpress' );?></button>
		<button class="lp-close-lightbox button" onclick="LP.MessageBox.hide();"><?php _e( 'Close', 'learnpress' ); ?></button>
	</div>
</script>