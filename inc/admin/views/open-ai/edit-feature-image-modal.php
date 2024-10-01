<?php
use LearnPress\Helpers\Config;

$config = Config::instance();
?>

<div id="lp-ai-course-edit-fi-modal" class="ai-modal">
	<div class="ai-overlay"></div>
	<div class="modal-content">
		<header class="modal-header">
			<div class="title"><?php esc_html_e( 'Edit Feature Image', 'learnpress' ); ?></div>
			<div class="close-btn">&times;</div>
		</header>
		<div class="content">
			<div class="ai-field">
				<div class="style">
					<label
						for="ai-course-edit-fi-field-style"><?php esc_html_e( 'Style', 'learnpress' ); ?></label>
					<?php
					$image_style_options = $config->get( 'image-styles', 'open-ai' );
					?>
					<select id="ai-course-edit-fi-field-style" class="lp-tom-select" multiple>
						<?php
						foreach ( $image_style_options as $value_attr => $image_style_option ) {
							?>
							<option
								value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $image_style_option ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="icon">
					<label
						for="ai-course-edit-fi-field-icon"><?php esc_html_e( 'Images or icons should be included', 'learnpress' ); ?></label>
					<textarea id="ai-course-edit-fi-field-icon" rows="3"
							  placeholder="<?php esc_attr_e( 'e.g.a computer', 'learnpress' ); ?>"></textarea>
				</div>

				<div class="logo">
					<label><?php esc_html_e( 'Branding Logo', 'learnpress' ); ?></label>
					<input type="file" id="ai-course-fi-field-logo-input" accept="image/png">
					<div id="ai-course-fi-field-logo-preview"></div>
					<div id="ai-course-fi-field-logo-error"></div>
					<p><?php esc_html_e( 'Must be a valid PNG file, less than 4MB, and square', 'learnpress' ); ?></p>
					<button id="ai-course-edit-fi-field-logo"
							class="button"><?php esc_html_e( 'Upload Image', 'learnpress' ); ?></button>
					<button id="ai-course-remove-fi-field-logo"
							class="button"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
				</div>

				<div class="mask">
					<label><?php esc_html_e( 'Mask', 'learnpress' ); ?></label>
					<input type="file" id="ai-course-fi-field-mask-input" accept="image/png">
					<div id="ai-course-fi-field-mask-preview"></div>
					<div id="ai-course-fi-field-mask-error"></div>
					<p><?php esc_html_e( 'Must be a valid PNG file, less than 4MB, and square', 'learnpress' ); ?></p>
					<button id="ai-course-edit-fi-field-mask"
							class="button"><?php esc_html_e( 'Upload Image', 'learnpress' ); ?></button>
					<button id="ai-course-remove-fi-field-mask"
							class="button"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
				</div>

				<div class="size">
					<label
						for="ai-course-edit-fi-field-size"><?php esc_html_e( 'Size', 'learnpress' ); ?></label>
					<?php
					$image_style_options = $config->get( 'image-dall-e-2-sizes', 'open-ai' );
					?>
					<select id="ai-course-edit-fi-field-size" class="lp-tom-select">
						<?php
						foreach ( $image_style_options as $value_attr => $image_style_option ) {
							?>
							<option
								value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $image_style_option ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="output">
					<div class="outputs">
						<label
							for="ai-course-edit-fi-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
						<input id="ai-course-edit-fi-field-outputs" type="number" min="1" step="1">
					</div>
					<button type="button"
							class="button"
							id="lp-generate-course-edit-fi-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
				<div class="course-edit-fi-output">
				</div>
			</div>
		</div>
	</div>
</div>
