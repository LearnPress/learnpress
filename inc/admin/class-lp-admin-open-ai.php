<?php
/**
 * Manage the admin open ai and display them in admin
 *
 * @package    LearnPress
 * @author     ThimPress
 * @version    1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Page;

/**
 * Class LP_Admin_Notice
 */
class LP_Admin_Open_Ai {
	/**
	 * @var bool
	 */
	protected static $instance = false;

	public function __construct() {
		if ( LP_Settings::instance()->get( 'enable_open_ai' ) !== 'yes' ) {
			return;
		}

		if ( empty( LP_Settings::instance()->get( 'open_ai_secret_key' ) ) ) {
			return;
		}

		if ( ! Page::is_admin_single_course_page() ) {
			add_action( 'admin_footer', array( $this, 'create_modals' ) );;
		}
	}

	/**
	 * @return void
	 */
	public function create_modals() {
		$this->add_course_title_modal();
		$this->add_course_des_modal();
		$this->add_course_edit_feature_image_modal();
	}

	/**
	 * @return void
	 */
	public function add_course_edit_feature_image_modal() {
		$lp_settings      = LP_Settings::instance();
		$image_model_type = $lp_settings->get( 'open_ai_image_model_type', 'dall-e-3' );
		$config = Config::instance();
		?>
		<div id="lp-ai-course-edit-fi-modal" class="ai-modal">
			<div class="modal-content">
				<header class="modal-header">
					<div class="title"><?php esc_html_e( 'Create Course Feature Image', 'learnpress' ); ?></div>
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
							<p><?php esc_html_e('Must be a valid PNG file, less than 4MB, and square', 'learnpress');?></p>
							<button id="ai-course-edit-fi-field-logo" class="button"><?php esc_html_e('Upload Image', 'learnpress');?></button>
							<button id="ai-course-remove-fi-field-logo" class="button"><?php esc_html_e('Remove', 'learnpress');?></button>
						</div>

						<?php
						if ( $image_model_type === 'dall-e-3' ) {
							?>
							<div class="quantity">
								<label
									for="ai-course-edit-fi-field-quality"><?php esc_html_e( 'Quality', 'learnpress' ); ?></label>
								<?php
								$quality_options = $config->get( 'image-quality', 'open-ai' );
								?>
								<select id="ai-course-edit-fi-field-quality" class="lp-tom-select">
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
							<?php
						}
						?>

						<div class="size">
							<label
								for="ai-course-edit-fi-field-size"><?php esc_html_e( 'Size', 'learnpress' ); ?></label>
							<?php

							if ( $image_model_type === 'dall-e-2' ) {
								$image_style_options = $config->get( 'image-dall-e-2-sizes', 'open-ai' );
							} else {
								$image_style_options = $config->get( 'image-dall-e-3-sizes', 'open-ai' );
							}
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
		<?php
	}

	/**
	 * @return void
	 */
	public function add_course_des_modal() {
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
						<div class="language">
							<label
								for="ai-course-des-field-language"><?php esc_html_e( 'Output Language', 'learnpress' ); ?></label>
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
		<?php
	}

	/**
	 * @return void
	 */
	public function add_course_title_modal() {
		$config = Config::instance();
		?>
		<div id="lp-ai-course-title-modal" class="ai-modal">
			<div class="modal-content">
				<header class="modal-header">
					<div class="title"><?php esc_html_e( 'Create Course Title', 'learnpress' ); ?></div>
					<div class="close-btn">&times;</div>
				</header>
				<div class="content">
					<div class="ai-field">
						<div class="topic">
							<label
								for="ai-course-title-field-topic"><?php esc_html_e( 'Describe what your course is about', 'learnpress' ); ?></label>
							<textarea id="ai-course-title-field-topic" rows="3"
									  placeholder="<?php esc_attr_e( 'e.g.A course to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
						</div>
						<div class="goal">
							<label
								for="ai-course-title-field-goal"><?php esc_html_e( 'Describe the main goals of your course', 'learnpress' ); ?></label>
							<textarea id="ai-course-title-field-goal" rows="3"
									  placeholder="<?php esc_attr_e( 'e.g.A course to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
						</div>
						<div class="audience">
							<label
								for="ai-course-title-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
							<?php
							$audience_options = $config->get( 'audience-options', 'open-ai' );
							?>
							<select id="ai-course-title-field-audience" class="lp-tom-select" multiple>
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
							<label for="ai-course-title-field-tone"><?php esc_html_e( 'Tone', 'learnpress' ); ?></label>
							<?php
							$tone_options = $config->get( 'tones', 'open-ai' );
							?>
							<select id="ai-course-title-field-tone" class="lp-tom-select" multiple>
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
								for="ai-course-title-field-language"><?php esc_html_e( 'Output Language', 'learnpress' ); ?></label>
							<?php
							$lang_options = $config->get( 'languages', 'open-ai' );
							?>
							<select id="ai-course-title-field-language" class="lp-tom-select" multiple>
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
									for="ai-course-title-field-outputs"><?php esc_html_e( 'Outputs', 'learnpress' ); ?></label>
								<input id="ai-course-title-field-outputs" type="number" min="1" step="1">
							</div>
							<button type="button"
									class="button"
									id="lp-generate-course-title-btn"><?php esc_html_e( 'Generate', 'learnpress' ); ?></button>
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
						<div class="course-title-output">
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @return bool|LP_Admin_Open_Ai
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Admin_Open_Ai::instance();
