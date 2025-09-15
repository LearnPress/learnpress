<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Settings;

/**
 * Class AdminEditCourseAI
 *
 * @since 4.2.9
 * @version 1.1.0
 */
class AdminEditCourseAI
{
	use Singleton;

	/**
	 * @var array|null
	 */
	private $config = null;

	/**
	 * Init hooks.
	 */
	public function init()
	{
		add_action('admin_enqueue_scripts', [$this, 'localize_script_data']);
	}

	/**
	 * Get config for OpenAI settings.
	 *
	 * @return array
	 */
	private function _get_config(): array
	{
		if (is_null($this->config)) {
			$this->config = Config::instance()->get('open-ai-modal', 'settings');
		}

		return $this->config;
	}

	/**
	 * Provides data to the frontend JavaScript using wp_localize_script.
	 *
	 * @param string $hook
	 */
	public function localize_script_data(string $hook)
	{
		// Only load on the course edit page.
		$screen = get_current_screen();
		wp_enqueue_script('lp-edit-course', plugins_url(""), ['jquery'], '1.0.0', true);

		if (!$screen || $screen->id !== 'lp_course') {
			return;
		}

		$config = $this->_get_config();
		$modelImage = LP_Settings::instance()->get_option('open_ai_image_model_type', 'dall-e-3');

		$data = [
			'nonce'      => wp_create_nonce('wp_rest'),
			'ajaxUrl'    => admin_url('index.php'),
			'options'    => [
				'audience'     => $config['audience-options'] ?? [],
				'tone'         => $config['tone-options'] ?? [],
				'language'     => $config['language-options'] ?? [],
				'levels'       => $config['levels'] ?? [],
				'imageStyle'   => $config['image-style-options'] ?? [],
				'imageSize'    => ($modelImage === 'dall-e-3')
					? ($config['image-dall-e-3-sizes-options'] ?? [])
					: ($config['image-dall-e-2-sizes-options'] ?? []),
				'imageQuality' => $config['image-quality-options'] ?? [],
			],
			'modelImage' => $modelImage,
			'i18n'       => [
				'createCourseTitle'       => esc_html__('Create Course Title', 'learnpress'),
				'describeCourseAbout'     => esc_html__('Describe what your course is about',
					'learnpress'),
				'describeCourseGoals'     => esc_html__('Describe the main goals of your course',
					'learnpress'),
				'createCourseDescription' => esc_html__('Create Course Description', 'learnpress'),
				'describeCourseStandOut'  => esc_html__('Describe what makes this course stand out?',
					'learnpress'),
				'createFeaturedImage'     => esc_html__('Create Featured Image', 'learnpress'),
				'style'                   => esc_html__('Style', 'learnpress'),
				'imagesOrIcons'           => esc_html__('Images or icons should be included',
					'learnpress'),
				'sizeImage'               => esc_html__('Size Image', 'learnpress'),
				'qualityImage'            => esc_html__('Quality Image', 'learnpress'),
				'createCourseCurriculum'  => esc_html__('Create Course Curriculum', 'learnpress'),
				'sections'                => esc_html__('Sections', 'learnpress'),
				'lessonsPerSection'       => esc_html__('Lessons per section', 'learnpress'),
				'levels'                  => esc_html__('Levels', 'learnpress'),
				'specificKeyTopics'       => esc_html__('Specific key topics', 'learnpress'),
				'audience'                => esc_html__('Audience', 'learnpress'),
				'tone'                    => esc_html__('Tone', 'learnpress'),
				'outputLanguage'          => esc_html__('Output Language', 'learnpress'),
				'outputs'                 => esc_html__('Outputs', 'learnpress'),
				'generate'                => esc_html__('Generate', 'learnpress'),
				'generating'              => esc_html__('Generating...', 'learnpress'),
				'pleaseWait'              => esc_html__('Please wait while we create the content.',
					'learnpress'),
				'results'                 => esc_html__('Generated Results', 'learnpress'),
				'errorOccurred'           => esc_html__('An error occurred', 'learnpress'),
				'applied'                 => esc_html__('Applied!', 'learnpress'),
				'copied'                 => esc_html__('Copied!', 'learnpress'),
				'copy'                 => esc_html__('Copy', 'learnpress'),
				'apply'                 => esc_html__('Apply', 'learnpress'),
			],
		];

		wp_localize_script('lp-edit-course', 'lpCourseAiModalData', $data);
	}
}
