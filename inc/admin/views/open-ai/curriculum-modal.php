<?php

use LearnPress\Helpers\Config;

$config = Config::instance();

if ( ! isset( $data ) ) {
	return;
}
$course_id = $data['course-id'] ?? '';
?>
<div id="lp-ai-curriculum-modal" class="ai-modal" data-course-id="<?php echo esc_attr( $course_id ); ?>">
	<div class="ai-overlay"></div>
	<div class="modal-content">
		<header class="modal-header">
			<div
				class="title"><?php esc_html_e( 'Create Course Curriculum - Introduction LearnPress - LMS Plugin', 'learnpress' ); ?></div>
			<div class="close-btn">&times;</div>
		</header>
		<div class="content">
			<div class="ai-field">
				<div class="section-numbers">
					<label
						for="ai-curriculum-field-section-numbers"><?php esc_html_e( 'Sections', 'learnpress' ); ?></label>
					<input id="ai-curriculum-field-section-numbers" type="number" min="1" step="1" value="2">
				</div>
				<div class="lesson-per-section">
					<label
						for="ai-curriculum-field-less-per-section"><?php esc_html_e( 'Lessons per section', 'learnpress' ); ?></label>
					<input id="ai-curriculum-field-less-per-section" type="number" min="1" step="1" value="2">
				</div>
				<div class="level">
					<label
						for="ai-curriculum-field-level"><?php esc_html_e( 'Levels', 'learnpress' ); ?></label>
					<?php
					$level_options = lp_course_level();
					?>
					<select id="ai-curriculum-field-level" class="lp-tom-select">
						<?php
						foreach ( $level_options as $level_option ) {
							?>
							<option
								value="<?php echo esc_attr( $level_option ); ?>"><?php echo esc_html( $level_option ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="topic">
					<label
						for="ai-curriculum-field-topic"><?php esc_html_e( 'Specific key topics', 'learnpress' ); ?></label>
					<textarea id="ai-curriculum-field-topic" rows="5"
							  placeholder="<?php esc_attr_e( 'e.g. Most common mistakes', 'learnpress' ); ?>"></textarea>
				</div>
				<div class="language">
					<label
						for="ai-curriculum-field-language"><?php esc_html_e( 'Output language', 'learnpress' ); ?></label>
					<?php
					$lang_options = $config->get( 'languages', 'open-ai' );
					?>
					<select id="ai-curriculum-field-language" class="lp-tom-select" multiple>
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
					<!--					<div class="outputs">-->
					<!--						<label-->
					<!--							for="ai-curriculum-field-outputs">-->
					<?php //esc_html_e( 'Outputs', 'learnpress' ); ?><!--</label>-->
					<!--						<input id="ai-curriculum-field-outputs" type="number" min="1" step="1">-->
					<!--					</div>-->
					<button type="button"
							class="button"
							id="lp-generate-curriculum-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
				<div class="curriculum-output">
				</div>
			</div>
		</div>
	</div>
</div>
