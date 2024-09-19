<?php
use LearnPress\Helpers\Config;

$config = Config::instance();

?>
<div id="lp-ai-course-des-modal" class="ai-modal">
	<div class="modal-content">
		<header class="modal-header">
			<div class="title"><?php esc_html_e( 'Create Course Description', 'learnpress' ); ?></div>
			<div class="close-btn">&times;</div>
		</header>
		<div class="content">
			<div class="ai-field">
				<div class="topic">
					<label
						for="ai-course-des-field-topic"><?php esc_html_e( 'Describe what make this course stand out', 'learnpress' ); ?></label>
					<textarea id="ai-course-des-field-topic" rows="5"
							  placeholder="<?php esc_attr_e( 'e.g.A course to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
				</div>
				<div class="audience">
					<label
						for="ai-course-des-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
					<?php
					$audience_options = $config->get( 'audiences', 'open-ai' );
					?>
					<select id="ai-course-des-field-audience" class="lp-tom-select" multiple>
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
					<label for="ai-course-des-field-tone"><?php esc_html_e( 'Tone', 'learnpress' ); ?></label>
					<?php
					$tone_options = $config->get( 'tones', 'open-ai' );
					?>
					<select id="ai-course-des-field-tone" class="lp-tom-select" multiple>
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

				<div class="paragraph-number">
					<label
						for="ai-course-des-field-paragraph-number"><?php esc_html_e( 'Paragraph Number', 'learnpress' ); ?></label>
					<input id="ai-course-des-field-paragraph-number" type="number" min="1" step="1" value="2">
				</div>

				<div class="language">
					<label
						for="ai-course-des-field-language"><?php esc_html_e( 'Output language', 'learnpress' ); ?></label>
					<?php
					$lang_options = $config->get( 'languages', 'open-ai' );
					?>
					<select id="ai-course-des-field-language" class="lp-tom-select" multiple>
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
							for="ai-course-des-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
						<input id="ai-course-des-field-outputs" type="number" min="1" step="1">
					</div>
					<button type="button"
							class="button"
							id="lp-generate-course-des-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
				<div class="course-des-output">
				</div>
			</div>
		</div>
	</div>
</div>
