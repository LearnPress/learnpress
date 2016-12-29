<?php
require_once LP_PLUGIN_PATH."/inc/shortcodes/class-lp-abstract-archive-shortcode.php";

/**
 * Shortcode to display collection of recent courses
 * ------GENERAL SYNTAX----------
 * [recent_course
 *          title=""
 *          limit="10"
 *          display="5"
 *          show_desc="true"
 *          show_thumbnail="true"
 *          show_enrolled_students="true"
 *          show_teacher="true"
 *          css_class=""
 *          template = "list"
 *
 *          items=""
 *          items_desktop="", //null or number
 *          items_desktop_small="",
 *          itemsTablet= "",
 *          items_tablet_small="",
 *          items_mobile= "",
 *          single_item= "false",
 *          items_scale_up= "true",
 *          slide_speed= "200",
 *          pagination_Speed= "800",
 *          rewind_speed= "1000",
 *          auto_play= "false",
 *          stop_on_hover= "true",
 *          navigation= "true",
 *          navigation_text_next= "&rarr;",
 *          navigation_text_prev= "&larr;",
 *          scroll_per_age= "false",
 *          pagination= "false",
 *          auto_height= "false"
 * ]
 *
 */

/**
 * -----OPTIONS-------
 * -Title:
 * the title of collection
 * empty by default
 *
 * -Limit:
 *  number, limit of the records will be  queried by get_courses function
 *
 * -Display:
 *  number of items display on each carousel item
 *  only used  in list template
 *
 * -show_thumbnail:
 *  set whether show course cover picture or not
 *  "true" by default
 *
 * -show_desc
 * set whether show course description
 * "false" by default
 *
 * -show_enrolled_students
 *  set whether show course's enrolled students
 *  "true" by default
 *
 * -show_teacher:
 *  set whether show course's instructor
 *  "true" by default
 *
 * -css_class:
 * additional custom css class
 *
 * -Templates:
 * there are 3 standard templates  is list, grids and cards (list by default)
 * you can also add your custom template by adding php file into templates folder
 *
 * - options for owl carousel (from items to the end)
 * shortcode support some owl carousel slider options
 *
 * please take a look  to owl-carousel docs to understand
 * documentation: http://owlgraphic.com/owlcarousel/#customizing
 *
 * the responsive options (items to mobile_items) use short code parameters as the second
 * element of owl carousel option array
 * navigation_text_prev and navigation_text_next is options for navigation button's content
 * by default is left arrow and right arrow
 * -----USAGE----------
 * [recent_course title="Recent courses" template="cards" show_desc="false" limit="20" display="8"]
 */


if ( !class_exists( 'LP_Recent_Courses_Shortcode' ) ) {
    /**
     * Class LP_Recent_Courses_Shortcode
     */
    class LP_Recent_Courses_Shortcode extends LP_Archive_Courses_Shortcode
    {
        protected static $name = "recent_course";

        public static function get_courses($a)
        {
            global $wpdb;
            $posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT ID FROM $wpdb->posts AS p
        			WHERE p.post_type = %s
		        	AND p.post_status = %s
			        ORDER BY p.post_date DESC
			        LIMIT %d",
                    LP_COURSE_CPT,
                    'publish',
                    (int)$a['limit']
                )
            );

            $courses = array_map(array(__CLASS__, 'get_lp_course'), $posts);
            
            return $courses;

        }
    }

    LP_Recent_Courses_Shortcode::init();
}