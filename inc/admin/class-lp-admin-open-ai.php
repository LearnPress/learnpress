<?php
/**
 * Manage the admin notices and display them in admin
 *
 * @package    LearnPress
 * @author     ThimPress
 * @version    1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnPress\Helpers\Config;

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

		add_action( 'edit_form_before_permalink', array( $this, 'add_edit_title_ai_button' ) );
		add_action( 'admin_footer', array( $this, 'add_course_title_ai_modal' ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function get_course_title( \WP_REST_Request $request ) {
		$params = $request->get_params();
		echo '<pre>';
		print_r( $params );
		echo '</pre>';

		die;
	}

	/**
	 * @param $post
	 *
	 * @return void
	 */
	public function add_edit_title_ai_button( $post ) {
		if ( $post->post_type !== LP_COURSE_CPT ) {
			return;
		}

		?>
		<button type="button" class="button"
				id="lp-edit-ai-course-title"><?php esc_attr_e( 'Edit with AI', 'learnpress' ); ?></button>
		<?php
	}

	public function add_course_title_ai_modal() {
		?>
		<div id="lp-ai-course-title-modal">
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
							<textarea id="ai-course-title-field-topic" cols="3"
									  placeholder="<?php esc_attr_e( 'e.g.A course to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
						</div>
						<div class="goal">
							<label
								for="ai-course-title-field-goal"><?php esc_html_e( 'Describe the main goals of your course', 'learnpress' ); ?></label>
							<textarea id="ai-course-title-field-goal" cols="3"
									  placeholder="<?php esc_attr_e( 'e.g.A course to teach how to use LearnPress', 'learnpress' ); ?>"></textarea>
						</div>
						<div class="audience">
							<label
								for="ai-course-title-field-audience"><?php esc_html_e( 'Audience', 'learnpress' ); ?></label>
							<?php
							$audience_options = Config::instance()->get( 'audience-options', 'open-ai' );
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
							$tone_options = Config::instance()->get( 'tone-options', 'open-ai' );
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
							$lang_options = Config::instance()->get( 'language-options', 'open-ai' );
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
