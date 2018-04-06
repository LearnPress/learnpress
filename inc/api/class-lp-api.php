<?php
class LP_API{
	/**
	 * @var object
	 */
	private static $_instance = false;

    //api endpoint for url
    protected $api_endpoint = 'lp-api-v1';
    //an array that holds string names supported cpts
    static $supported_items = array('courses', 'lessons');


    function __construct(){
        // hook into rest api initialization process, register our route
        add_action( 'rest_api_init', array($this, 'register_lp_course_rest_ep'));
    }

    function register_lp_course_rest_ep(){
        register_rest_route( $this->api_endpoint,'/courses', array(
            'methods' => 'GET',
            'callback' => array($this,'get_REST_courses'),/*
            'args' => array(
              'id' => array(
                'validate_callback' => function($param, $request, $key) {
                  return is_numeric( $param );
                }
              ),
            ),*/
          ) );
    }
    // returns the courses at registered endpoint
    function get_REST_courses () {
        $all_courses = $this->get_courses();
        return $all_courses;
    }
    // bit of sql to get all the courses
    function get_courses() {
		global $wpdb;
		$post_type    = 'lp_course';
		$query        = $wpdb->prepare( "
			SELECT ID, post_title
			FROM {$wpdb->posts}
			WHERE post_type = %s AND post_status = %s
        ", $post_type, 'publish' );
        $courses = $wpdb->get_results( $query );
        return $courses;
    }
    static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
add_action( 'learn_press_loaded', array( 'LP_API', 'instance' ) );
