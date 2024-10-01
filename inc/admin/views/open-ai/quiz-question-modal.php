<?php
use LearnPress\Helpers\Config;

$config = Config::instance();

if ( ! isset( $data ) ) {
	return;
}
$quiz_id = $data['quiz-id'] ?? '';
?>
	<div id="lp-ai-quiz-question-modal" class="ai-modal" data-quiz-id="<?php echo esc_attr($quiz_id);?>">
		<div class="ai-overlay"></div>

		<div class="modal-content">
			<header class="modal-header">
				<div class="title"><?php esc_html_e( 'Create Questions', 'learnpress' ); ?></div>
				<div class="close-btn">&times;</div>
			</header>
			<div class="content">
				<div class="ai-field">
					<div class="topic">
						<label
							for="ai-quiz-question-field-topic"><?php esc_html_e( 'Describe what your quiz is about', 'learnpress' ); ?></label>
						<textarea id="ai-quiz-question-field-topic" rows="3"
						          placeholder="<?php esc_attr_e( 'e.g.A quiz to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
					</div>
					<div class="goal">
						<label
							for="ai-quiz-question-field-goal"><?php esc_html_e( 'Describe the main goals of your quiz', 'learnpress' ); ?></label>
						<textarea id="ai-quiz-question-field-goal" rows="3"
						          placeholder="<?php esc_attr_e( 'e.g.A quiz to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
					</div>
					<div class="audience">
						<label
							for="ai-quiz-question-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
						<?php
						$audience_options = $config->get( 'audiences', 'open-ai' );
						?>
						<select id="ai-quiz-question-field-audience" class="lp-tom-select" multiple>
							<?php
							foreach ( $audience_options as $value_attr => $audience_option ) {
								?>
								<option
									value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $audience_option ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="tone">
						<label for="ai-quiz-question-field-tone"><?php esc_html_e( 'Tone', 'learnpress' ); ?></label>
						<?php
						$tone_options = $config->get( 'tones', 'open-ai' );
						?>
						<select id="ai-quiz-question-field-tone" class="lp-tom-select" multiple>
							<?php
							foreach ( $tone_options as $value_attr => $tone_option ) {
								?>
								<option
									value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $tone_option ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="type">
						<label for="ai-quiz-question-field-type"><?php esc_html_e( 'Question type', 'learnpress' ); ?></label>
						<?php
						$type_options = learn_press_get_question_type_support();
						?>
						<select id="ai-quiz-question-field-type" class="lp-tom-select" multiple>
							<?php
							foreach ( $type_options as $value_attr => $type_option ) {
								$label = explode('_', $value_attr);
								$label = array_map('ucfirst', $label);
								$label =  implode(' ', $label);
								?>
								<option
									value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="question-numbers">
						<label
							for="ai-quiz-question-field-number"><?php esc_html_e( 'Question number', 'learnpress' ); ?></label>
						<input id="ai-quiz-question-field-number" type="number" min="1" step="1" value="2">
					</div>
					<div class="language">
						<label
							for="ai-quiz-question-field-language"><?php esc_html_e( 'Output language', 'learnpress' ); ?></label>
						<?php
						$lang_options = $config->get( 'languages', 'open-ai' );
						?>
						<select id="ai-quiz-question-field-language" class="lp-tom-select" multiple>
							<?php
							foreach ( $lang_options as $value_attr => $lang_option ) {
								?>
								<option
									value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $lang_option ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="output">
						<button type="button"
						        class="button"
						        id="lp-generate-quiz-question-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
					</div>
				</div>
				<div class="ai-output">
					<header class="header">
						<div class="output-title"><?php esc_html_e( 'Output', 'learnpress' ); ?></div>
						<button type="button"
						        class="toggle-prompt button"><?php esc_html_e( 'Display prompt', 'learnpress' ); ?></button>
					</header>
					<div class="prompt-output">
					</div>
					<div class="quiz-question-output">
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
