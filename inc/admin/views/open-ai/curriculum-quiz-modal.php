<?php
use LearnPress\Helpers\Config;

$config = Config::instance();

if ( ! isset( $data ) ) {
	return;
}
$course_id = $data['course-id'] ?? '';
?>
	<div id="lp-ai-curriculum-quiz-modal" class="ai-modal" data-course-id="<?php echo esc_attr( $course_id ); ?>">
		<div class="ai-overlay"></div>
		<div class="modal-content">
			<header class="modal-header">
				<div class="title"><?php esc_html_e( 'Create Quiz Title', 'learnpress' ); ?></div>
				<div class="close-btn">&times;</div>
			</header>
			<div class="content">
				<div class="ai-field">
					<div class="topic">
						<label
							for="ai-curriculum-quiz-field-topic"><?php esc_html_e( 'Describe what your quiz is about', 'learnpress' ); ?></label>
						<textarea id="ai-curriculum-quiz-field-topic" rows="3"
						          placeholder="<?php esc_attr_e( 'e.g.A quiz to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
					</div>
					<div class="goal">
						<label
							for="ai-curriculum-quiz-field-goal"><?php esc_html_e( 'Describe the main goals of your quiz', 'learnpress' ); ?></label>
						<textarea id="ai-curriculum-quiz-field-goal" rows="3"
						          placeholder="<?php esc_attr_e( 'e.g.A quiz to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
					</div>
					<div class="audience">
						<label
							for="ai-curriculum-quiz-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
						<?php
						$audience_options = $config->get( 'audiences', 'open-ai' );
						?>
						<select id="ai-curriculum-quiz-field-audience" class="lp-tom-select" multiple>
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
						<label for="ai-curriculum-quiz-field-tone"><?php esc_html_e( 'Tone', 'learnpress' ); ?></label>
						<?php
						$tone_options = $config->get( 'tones', 'open-ai' );
						?>
						<select id="ai-curriculum-quiz-field-tone" class="lp-tom-select" multiple>
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
					<div class="language">
						<label
							for="ai-curriculum-quiz-field-language"><?php esc_html_e( 'Output language', 'learnpress' ); ?></label>
						<?php
						$lang_options = $config->get( 'languages', 'open-ai' );
						?>
						<select id="ai-curriculum-quiz-field-language" class="lp-tom-select" multiple>
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
						<div class="outputs">
							<label
								for="ai-curriculum-quiz-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
							<input id="ai-curriculum-quiz-field-outputs" type="number" min="1" step="1">
						</div>
						<button type="button"
						        class="button"
						        id="lp-generate-curriculum-quiz-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
					<div class="curriculum-quiz-output">
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
