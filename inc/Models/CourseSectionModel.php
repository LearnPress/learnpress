<?php

/**
 * Class Courses
 *
 * Handle all method about list courses
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5.4
 */

namespace learnpress\inc\Models;

use LP_Cache;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Courses_Cache;
use LP_Database;
use LP_Helper;
use LP_Settings;
use Thim_Cache_DB;
use Throwable;

class CourseSectionModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $section_id = 0;
	/**
	 * Title of the section
	 *
	 * @var int
	 */
	public $section_name = '';
	/**
	 * Foreign key, Course ID
	 *
	 * @var int
	 */
	public $section_course_id = 0;
	/**
	 * Order of the section
	 *
	 * @var int
	 */
	public $section_order = 0;
	/**
	 * Description of the section
	 *
	 * @var string
	 */
	public $section_description = '';
}
