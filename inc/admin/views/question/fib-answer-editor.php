<div class="lp-box-data-content">
	<div class="learn-press-question">
		<div class="content-editable" contenteditable="true" @mouseup="canInsertBlank" @mousedown="activeBlank" @keyup="updateAnswer"></div>
		<div class="description">
			<p><?php _e( 'Select a word in the passage above and click <strong>\'Insert a new blank\'</strong> to make that word a blank for filling.', 'learnpress' ); ?></p>
		</div>
		<p>
			<button class="button" type="button" @click="insertBlank" :disabled="!canInsertNewBlank"><?php esc_html_e( 'Insert a new blank', 'learnpress' ); ?></button>
			<button class="button" type="button" @click="clearBlanks" :disabled="blanks.length == 0"><?php esc_html_e( 'Remove all blanks', 'learnpress' ); ?></button>
			<button class="button" type="button" @click="clearContent"><?php esc_html_e( 'Clear content', 'learnpress' ); ?></button>
		</p>

		<table class="fib-blanks">
			<tbody v-for="blank in blanks" :data-id="'fib-blank-' + blank.id" class="fib-blank" :class="{ invalid: !blank.fill, open: blank.open }">
			<tr>
				<td class="blank-position" width="50">#{{blank.index}}</td>
				<td class="blank-fill">
					<input type="text" :id="'fib-blank-' + blank.id" v-model="blank.fill" @keyup="updateBlank" @change="updateBlank">
				</td>
				<td class="blank-actions">
					<span class="blank-status"></span>

					<a class="button" href="" @click="toggleOptions($event, blank.id)"><?php esc_html_e( 'Options', 'learnpress' ); ?></a>
					<a class="delete button" href="" @click="removeBlank($event, blank.id)"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a>
				</td>
			</tr>
			<tr class="blank-options">
				<td width="50"></td>
				<td colspan="2">
					<ul>
						<li>
							<label>
								<input type="checkbox" v-model="blank.match_case" @click="updateAnswerMatchCase($event, blank)">
								<?php esc_html_e( 'Match case', 'learnpress' ); ?></label>
							<p class="description"><?php esc_html_e( 'Match two words in case sensitive.', 'learnpress' ); ?></p>
						</li>
						<li><h4><?php esc_html_e( 'Comparison', 'learnpress' ); ?></h4></li>
						<li>
							<label>
								<input type="radio" value="" v-model="blank.comparison" @click="updateAnswerBlank($event, blank)">
								<?php esc_html_e( 'Equal', 'learnpress' ); ?></label>
							<p class="description"><?php esc_html_e( 'Match two words are equality.', 'learnpress' ); ?></p>
						</li>
						<li>
							<label>
								<input type="radio" value="range" v-model="blank.comparison" @click="updateAnswerBlank($event, blank)">
								<?php esc_html_e( 'Range', 'learnpress' ); ?></label>
							<p class="description"><?php _e( 'Match any number in a range. Use <code>100, 200</code> to match any value from 100 to 200.', 'learnpress' ); ?></p>
						</li>
						<li>
							<label>
								<input type="radio" value="any" v-model="blank.comparison" @click="updateAnswerBlank($event, blank)">
								<?php esc_html_e( 'Any', 'learnpress' ); ?></label>
							<p class="description"><?php _e( 'Match any value in a set of words. Use <code>fill, blank, or question</code> to match any value in the set.', 'learnpress' ); ?></p>
						</li>
					</ul>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
</div>
