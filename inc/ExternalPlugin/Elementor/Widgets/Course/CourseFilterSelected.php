<?php

/**
 * Class CourseFilterSelected
 *
 * @sicne 4.2.5
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use Throwable;

class CourseFilterSelected extends LPElementorWidgetBase 
{
    public function __construct($data = [], $args = null)
	{
		$this->title    = esc_html__('Course Filter Selected', 'learnpress');
		$this->name     = 'course_filter_selected';
		$this->keywords = ['course filter selected'];
		$this->icon     = 'eicon-form-vertical';

        parent::__construct($data, $args);
    }

    /**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls()
	{
		$this->controls = Config::instance()->get(
			'course-filter-selected',
			'elementor/course'
		);
		parent::register_controls();
	}

    protected function render()
	{
		try {
			$settings 	= $this->get_settings_for_display();
            $text_reset = $settings['text_reset'] ?? esc_html__('Clear', 'learnpress');

            echo '<div class="selected-list">';
            if (!empty($settings['show_preview'])) {
                echo '<span class="selected-item preview" >Preview 1<i class="icon-remove-selected fas fa-times"></i></span><span class="selected-item preview" >Preview 2<i class="icon-remove-selected fas fa-times"></i></span>';
                echo '<button class="clear-selected-list preview">'. $text_reset .'</button>';
            }

            echo self::selected_style_list();
            
            if ((!empty($_GET['term_id']) || !empty($_GET['tag_id']) || !empty($_GET['sort_by']) || !empty($_GET['c_level']) || !empty($_GET['c_authors'])) ) {
				echo '<button class="clear-selected-list">'. $text_reset .'</button>';
			}
            echo '</div>';

        } catch (Throwable $e) {
			error_log(__METHOD__ . ': ' . $e->getMessage());
		}
    }

    protected static function selected_style_list()
	{
		$cats = $tags = $authors = $levels = $icon_move = '';
		$classListItem = 'selected-item';
        $icon_move = '<i class="icon-remove-selected fas fa-times"></i>';


		if (!empty($_GET['term_id'])) {
			$cats = explode(',', $_GET['term_id']);
			foreach ($cats as $cat) {
				echo '<span class="' . $classListItem . '" data-name="term_id" data-value="' . $cat . '">' . get_term($cat, 'course_category')->name . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['tag_id'])) {
			$tags = explode(',', $_GET['tag_id']);
			foreach ($tags as $tag) {
				echo '<span class="' . $classListItem . '" data-name="tag_id" data-value="' . $tag . '">' . get_term($tag, 'course_tag')->name . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['sort_by'])) {
			if ($_GET['sort_by'] == 'on_free') {
				echo  '<span class="' . $classListItem . '" data-name="sort_by" data-value="' . $_GET['sort_by'] . '">' . __('Free', 'learnpress') . '' . $icon_move . '</span>';
			} else {
				echo '<span class="' . $classListItem . '" data-name="sort_by" data-value="' . $_GET['sort_by'] . '">' . __('Paid', 'learnpress') . '' . $icon_move . '</span>';
			}
		}

		if (!empty($_GET['c_level'])) {
            $levels = explode(',', $_GET['c_level']);
            foreach ($levels as $level) {
                if ($level == 'all') {
                    echo  '<span class="' . $classListItem . '" data-name="c_level" data-value="' . $level . '">' . __('All Levels', 'learnpress') . '' . $icon_move . '</span>';
                } else {
                    echo  '<span class="' . $classListItem . '" data-name="c_level" data-value="' . $level . '">' . $level . '' . $icon_move . '</span>';
                }
            }
		}

		if (!empty($_GET['c_authors'])) {
			$authors = explode(',', $_GET['c_authors']);
			foreach ($authors as $author) {
				$user = get_userdata($author);
				echo  '<span class="' . $classListItem . '" data-name="c_authors" data-value="' . $_GET['c_authors'] . '">' . $user->display_name . '' . $icon_move . '</span>';
			}
		}
	}
}