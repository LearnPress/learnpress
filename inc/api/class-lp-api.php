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
         // if you have the learning path plugin installed you can use this to pull 
         // your paths 
          register_rest_route( $this->api_endpoint,'/learningpaths', array(
            'methods' => 'GET',
            'callback' => array($this,'get_REST_learningpaths'),/*
            'args' => array(
              'id' => array(
                'validate_callback' => function($param, $request, $key) {
                  return is_numeric( $param );
                }
              ),
            ),*/
          ) );
            // gets the curriculum for a specified(id) course
          register_rest_route( $this->api_endpoint,'/courses/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'get_REST_course_curriculum'),
            'args' => array(
              'id' => array(
                'validate_callback' => function($param) {
                  return is_numeric( $param );
                }
              ),
            ),
          ) );
    }

    //get the course curriculum
    function get_REST_course_curriculum($data){
      $cID = $data['id'];
      $curr = learn_press_get_course_curriculum($cID);
      return $curr;
    }
    // returns the courses at registered endpoint
    function get_REST_courses () {
        $all_courses = $this->get_posts_by_type();
        return $all_courses;
    }
    // return the learning paths
    function get_REST_learningpaths () {
      $all_courses = $this->get_posts_by_type('lp_learning_path_cpt');
      return $all_courses;
  }
    // bit of sql to get all the courses
    function get_posts_by_type($type = 'lp_course') {
		global $wpdb;
		$post_type    =  $type;
		$query        = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_type = %s AND post_status = %s
        ", $post_type, 'publish' );
        $courses = $wpdb->get_results( $query );
        return $courses;
    }
    // get the course by id
    /*
    function get_course_by_id($id = 0) {
      global $wpdb;
      $post_id = $id;
      $query        = $wpdb->prepare( "
        SELECT *
        FROM {$wpdb->posts}
        WHERE ID = %s AND post_status = %s
          ", $post_id, 'publish' );
          $courses = $wpdb->get_results( $query );
          return $courses;
      }
      */
    static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
add_action( 'learn_press_loaded', array( 'LP_API', 'instance' ) );
