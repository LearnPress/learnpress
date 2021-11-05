<?php
class LP_Jwt_Course_Category_V1_Controller extends WP_REST_Terms_Controller {

	protected $taxonomy = 'course_category';

	public function __construct() {
		parent::__construct( $this->taxonomy );

		$this->namespace = 'learnpress/v1';
		$this->rest_base = 'course_category';
	}

	/**
	 * Register routes for courses.
	 */
	public function register_routes() {
		parent::register_routes();
	}
}
