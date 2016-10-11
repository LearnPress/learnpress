<?php
/**
 * Shortcodes to display archive courses
 */

/**
 *  Warning: this is a abstract class, do not create instance directly.
 *  Important note: It's is my the very first WP plugin, if find out any issue please report me (Minhlv) to fix it out ^^
 */

/**
 * Shortcode to display list of courses by custom query
 * ------GENERAL SYNTAX----------
 * [shortcode_name
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
 * create class that extends LP_Archive_Courses_Shortcode
 * implement get_course methods which return array of LP_Course to be display
 *
 * override optional methods
 *  add_default_atts to modify default attributes
 */

defined( 'ABSPATH' ) || exit();


if ( !class_exists( 'LP_Archive_Courses_Shortcode' ) ) {
	/**
	 * Class LP_AJAX
	 */
	class LP_Archive_Courses_Shortcode {
		/**
		 * @var array null shortcode attribute
		 */
		protected static $atts = null;

		/**
		 * @var string null template directory
		 */
		protected static $template_dir = null;

		/**
		 * @var string shortcode name
		 */

		protected static $name = "";

		/**
		 * @var string template file name
		 */
		protected static $template_file_name = null;

		/**
		 * @var null|string shortcode script url
		 */
		protected static $script_url = null;

		/**
		 * get shortcode be initial
		 * call once
		 */
		public static function init() {
			static::init_const();
			static::enqueue_style();
			add_shortcode( static::$name, array( get_called_class(), 'shortcode_output' ) );
		}

		/**
		 * get learn press course from wordpress post object
		 *
		 * @param object -reference $post wordpress post object
		 *
		 * @return LP_Course course
		 */
		public static function get_lp_course( $post ) {
			$id     = $post->ID;
			$course = null;
			if ( !empty( $id ) ) {
				$course = new LP_Course( $id );
			}
			return $course;
		}

		/**
		 * get shortcode output
		 * main function of shortcode
		 */
		public static function shortcode_output( $atts ) {
			$default_atts = static::default_atts();
			$a            = shortcode_atts( $default_atts, $atts );
			$a            = static::parse_atts( $a );

			$template_file_name = static::get_template( $a );
			$courses            = static::get_courses( $a );

			if ( empty( $courses ) ) return;
			$output = static::render( $courses, $a, $template_file_name );
			return $output;
		}

		/**
		 * parse shortcode input array
		 *
		 * @param array $atts
		 *
		 * @return array well parsed shortcode attributes
		 */
		public static function parse_atts( $a ) {
			$a['show_desc']               = filter_var( $a['show_desc'], FILTER_VALIDATE_BOOLEAN );
			$a['show_lesson']             = filter_var( $a['show_lesson'], FILTER_VALIDATE_BOOLEAN );
			$a['show_thumbnail']          = filter_var( $a['show_thumbnail'], FILTER_VALIDATE_BOOLEAN );
			$a['show_enrolled_students']  = filter_var( $a['show_enrolled_students'], FILTER_VALIDATE_BOOLEAN );
			$a['show_teacher']            = filter_var( $a['show_teacher'], FILTER_VALIDATE_BOOLEAN );
			$a['show_actions']            = filter_var( $a['show_actions'], FILTER_VALIDATE_BOOLEAN );
			$a['show_action_view_course'] = filter_var( $a['show_action_view_course'], FILTER_VALIDATE_BOOLEAN );
			$a['show_price']              = filter_var( $a['show_price'], FILTER_VALIDATE_BOOLEAN );
			$a['limit']                   = intval( $a['limit'] );
			$a['display']                 = intval( $a['display'] );

			return $a;
		}

		/**
		 * get archive course by attributes
		 * @return /LP_Course[] array of courses
		 */
		public static function get_courses( $a ) {
			//TODO
			return null;
		}

		/**
		 * include layout
		 *
		 * @param $template_name
		 *
		 * @return string
		 */
		public static function get_template( $atts ) {
			$template_file_name = $atts['template'] . '.php';
			if ( !file_exists( static::$template_dir . $template_file_name ) ) {
				$template_file_name = 'list.php';
			}
			return $template_file_name;
		}

		/**
		 * include course template
		 *
		 * @param LP_Course current course
		 */
		public static function render( $courses, $a = null, $template_file_name = 'cards.php' ) {

			$courses_count = sizeof( $courses );
			if ( $a['display'] > $courses_count ) {
				$a['display'] = $courses_count;
			}

			ob_start();
			$page_count = floor( $courses_count / $a['display'] );
			$page_count += ( $courses_count % $a['display'] == 0 ) ? 0 : 1;

			//include template file
			include static::$template_dir . $template_file_name;

			if ( isset( $template_script ) ) {
				static::enqueue_scripts
				( $template_script );
			}

			return ob_get_clean();
		}

		/**
		 * add shortcode style
		 */
		public static function enqueue_style() {
			add_action( 'wp_head', array( __CLASS__, 'apply_style' ) );
		}

		public static function apply_style() {
			wp_enqueue_style( 'owl_carousel_css', LP()->css( 'owl.carousel.css' ) );
		}

		/**
		 * apply shortcode style
		 */
		/**
		 * add shortcode script
		 *
		 * @param $script_url script url
		 */
		public static function enqueue_scripts( $script_url ) {
			static::$script_url = $script_url;
			add_action( 'wp_footer', array( __CLASS__, 'apply_script' ) );
		}

		/**
		 * callback to apply shortcode script
		 */
		public static function apply_script() {
			$url = static::$script_url;
			$owl = LP()->js( 'owl.carousel.min.js' );
			wp_enqueue_script( 'owl_carousel_js', $owl, array( 'jquery' ) );
			wp_enqueue_script( 'lp_shortcode_archive_course', $url, array( 'jquery' ) );
		}

		/**
		 * define static variable
		 */
		public static function init_const() {
			static::$template_dir = LP_PLUGIN_PATH . '/inc/shortcodes/recent_courses/templates/';
		}

		/**
		 * add default attributes
		 * return array
		 */
		public static function add_default_atts() {
			return null;
		}

		/**
		 * define default shortcode attributes
		 *
		 * @param array
		 *
		 * @return  array default attributes
		 */
		public static function default_atts() {
			$a = array(
				'limit'                   => '8',
				'display'                 => '8',
				'title'                   => "",
				'show_desc'               => 'false',
				'show_thumbnail'          => 'true',
				'show_lesson'             => 'true',
				'show_enrolled_students'  => 'true',
				'show_teacher'            => 'true',
				'show_actions'            => 'false',
				'show_action_view_course' => 'true',
				'show_price'              => 'true',
				'css_class'               => '',
				'template'                => 'list',

				//options for owl carousel
				'items'                   => "",
				'items_desktop'           => "",
				'items_desktop_small'     => "",
				'itemsTablet'             => "",
				'items_tablet_small'      => "",
				'items_mobile'            => "",
				'single_item'             => "false",
				'items_scale_up'          => "false",
				'slide_speed'             => "200",
				'pagination_Speed'        => "800",
				'rewind_speed'            => "1000",
				'auto_play'               => "false",
				'stop_on_hover'           => "true",
				'navigation'              => "true",
				'navigation_text_next'    => '&rarr;',
				'navigation_text_prev'    => '&larr;',
				'scroll_per_page'         => "false",
				'pagination'              => "false",
				'auto_height'             => "false"
			);

			$add_atts = static::add_default_atts();
			return wp_parse_args( $a, $add_atts );
		}

	}
}