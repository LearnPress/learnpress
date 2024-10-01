<?php
use LearnPress\Helpers\Config;

$config = Config::instance();
?>
<div id="lp-ai-question-des-modal" class="ai-modal">
	<div class="ai-overlay"></div>
	<div class="modal-content">
		<header class="modal-header">
			<div class="title"><?php esc_html_e( 'Create Question Description', 'learnpress' ); ?></div>
			<div class="close-btn">&times;</div>
		</header>
		<div class="content">
			<div class="ai-field">
				<div class="topic">
					<label
						for="ai-question-des-field-topic"><?php esc_html_e( 'Describe what make this question stand out', 'learnpress' ); ?></label>
					<textarea id="ai-question-des-field-topic" rows="5"
					          placeholder="<?php esc_attr_e( 'e.g.A question to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
				</div>
				<div class="audience">
					<label
						for="ai-question-des-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
					<?php
					$audience_options = $config->get( 'audiences', 'open-ai' );
					?>
					<select id="ai-question-des-field-audience" class="lp-tom-select" multiple>
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
					<label for="ai-question-des-field-tone"><?php esc_html_e( 'Tone', 'learnpress' ); ?></label>
					<?php
					$tone_options = $config->get( 'tones', 'open-ai' );
					?>
					<select id="ai-question-des-field-tone" class="lp-tom-select" multiple>
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
						for="ai-question-des-field-language"><?php esc_html_e( 'Output language', 'learnpress' ); ?></label>
					<?php
					$lang_options = $config->get( 'languages', 'open-ai' );
					?>
					<select id="ai-question-des-field-language" class="lp-tom-select" multiple>
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
							for="ai-question-des-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
						<input id="ai-question-des-field-outputs" type="number" min="1" step="1">
					</div>
					<button type="button"
					        class="button"
					        id="lp-generate-question-des-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
				</div>
			</div>
			<div class="ai-output">
				<header class="header">
					<div class="output-des"><?php esc_html_e( 'Output', 'learnpress' ); ?></div>
					<button type="button"
					        class="toggle-prompt button"><?php esc_html_e( 'Display prompt', 'learnpress' ); ?></button>
				</header>
				<div class="prompt-output">
				</div>
				<div class="question-des-output">
				</div>
			</div>
		</div>
	</div>
</div>
