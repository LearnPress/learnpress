<?php
use LearnPress\Helpers\Config;

$config = Config::instance();
$lp_setting = LP_Settings::instance();
$model      = $lp_setting->get( 'open_ai_image_model_type' );
?>

<div id="lp-ai-course-create-fi-modal" class="ai-modal">
	<div class="modal-content">
		<header class="modal-header">
			<div class="title"><?php esc_html_e( 'Create Feature Image', 'learnpress' ); ?></div>
			<div class="close-btn">&times;</div>
		</header>
		<div class="content">
			<div class="ai-field">
				<div class="style">
					<label
						for="ai-course-create-fi-field-style"><?php esc_html_e( 'Style', 'learnpress' ); ?></label>
					<?php
					$image_style_options = $config->get( 'image-styles', 'open-ai' );
					?>
					<select id="ai-course-create-fi-field-style" class="lp-tom-select" multiple>
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
						for="ai-course-create-fi-field-icon"><?php esc_html_e( 'Images or icons should be included', 'learnpress' ); ?></label>
					<textarea id="ai-course-create-fi-field-icon" rows="3"
					          placeholder="<?php esc_attr_e( 'e.g.a computer', 'learnpress' ); ?>"></textarea>
				</div>

				<div class="size">
					<label
						for="ai-course-create-fi-field-size"><?php esc_html_e( 'Size', 'learnpress' ); ?></label>
					<?php
					if ( $model === 'dall-e-2' ) {
						$image_style_options = $config->get( 'image-dall-e-2-sizes', 'open-ai' );
					} else {
						$image_style_options = $config->get( 'image-dall-e-3-sizes', 'open-ai' );
					}
					?>
					<select id="ai-course-create-fi-field-size" class="lp-tom-select">
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
				<div class="quality">
					<label
						for="ai-course-create-fi-field-quality"><?php esc_html_e( 'Quality', 'learnpress' ); ?></label>
					<?php
					$quality_options = $config->get( 'image-quality', 'open-ai' );
					?>
					<select id="ai-course-create-fi-field-quality" class="lp-tom-select">
						<?php
						foreach ( $quality_options as $value_attr => $quality_option ) {
							?>
							<option
								value="<?php echo esc_attr( $value_attr ); ?>"><?php echo esc_html( $quality_option ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="output">
					<?php
					if ( $model === 'dall-e-2' ) {
						?>
						<div class="outputs">
							<label
								for="ai-course-create-fi-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
							<input id="ai-course-create-fi-field-outputs" type="number" min="1" step="1">
						</div>
						<?php
					}
					?>
					<button type="button"
					        class="button"
					        id="lp-generate-course-create-fi-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
				<div class="course-create-fi-output">
				</div>
			</div>
		</div>
	</div>
</div>
