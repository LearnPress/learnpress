<?php
namespace Elementor;

class LP_Elementor_Widget_List_Courses extends LP_Elementor_Widget_Base {

	public function get_name() {
		return 'learnpress_list_courses';
	}

	public function get_title() {
		return esc_html__( 'List Courses', 'learnpress' );
	}

	public function get_keywords() {
		return array( 'learnpress', 'list courses' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	protected function register_controls() {

		$this->_register_control_content();

	}

	protected function _register_control_content() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'learnpress' ),
			)
		);
		$this->add_control(
			'grid_list_type',
			array(
				'label'   => esc_html__( 'Layout Type', 'learnpress' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'list' => esc_html__( 'List', 'learnpress' ),
					'grid' => esc_html__( 'Grid', 'learnpress' ),
				),
				'default' => 'grid',
			)
		);
		$this->add_control(
			'post_id',
			array(
				'label'   => esc_html__( 'Select Course', 'learnpress' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_all_courses( array( '0' => esc_html__( 'All', 'learnpress' ) ) ),
				'default' => '0',
			)
		);
		$this->add_control(
			'cat_id',
			array(
				'label'   => esc_html__( 'Select Category', 'learnpress' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_cat_taxonomy( array( '0' => esc_html__( 'All', 'learnpress' ) ) ),
				'default' => '0',
			)
		);
		$this->add_control(
			'course_type',
			array(
				'label'   => esc_html__( 'Course Type', 'learnpress' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					''         => esc_html__( 'Default', 'learnpress' ),
					'recent'   => esc_html__( 'Recent', 'learnpress' ),
					'popular'  => esc_html__( 'Popular', 'learnpress' ),
					'featured' => esc_html__( 'Featured', 'learnpress' ),
				),
				'default' => '',
			)
		);
		$this->add_control(
			'order_by',
			array(
				'label'     => esc_html__( 'Order By', 'learnpress' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'desc' => esc_html__( 'DESC', 'learnpress' ),
					'asc'  => esc_html__( 'ASC', 'learnpress' ),
				),
				'default'   => 'desc',
				'condition' => array(
					'course_type!' => 'popular',
				),
			)
		);
		$this->add_control(
			'number_posts',
			array(
				'label'   => esc_html__( 'Number Post', 'learnpress' ),
				'default' => '4',
				'type'    => Controls_Manager::NUMBER,
			)
		);

		$this->end_controls_section();
	}

	protected function get_cat_taxonomy( $cats = array() ) {

		$terms = new \WP_Term_Query(
			array(
				'taxonomy'     => 'course_category',
				'pad_counts'   => 1,
				'hierarchical' => 1,
				'hide_empty'   => 1,
				'orderby'      => 'name',
				'menu_order'   => true,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $cats;
		} else {
			if ( empty( $terms->terms ) ) {
			} else {
				foreach ( $terms->terms as $term ) {
					$prefix = '';
					if ( $term->parent > 0 ) {
						$prefix = '--';
					}
					$cats[ $term->term_id ] = $prefix . $term->name;
				}
			}
		}

		return $cats;
	}

	protected function get_all_courses( $data = array() ) {
		$args    = array(
			'post_type'      => LP_COURSE_CPT,
			'posts_per_page' => -1,
		);
		$courses = get_posts( $args );
		if ( is_wp_error( $courses ) ) {
			return $data;
		}
		foreach ( $courses as $course ) {
			$data[ $course->ID ] = $course->post_title;
		}

		return $data;
	}

	public function render() {
		$settings      = $this->get_settings_for_display();
		$filter        = new \LP_Course_Filter();
		$filter->limit = $settings['number_posts'];
		$course_type   = $settings['course_type'];
		$courses       = array();

		if ( $settings['cat_id'] ) {
			$filter->term_ids = array( $settings['cat_id'] );
		}

		if ( $settings['order_by'] ) {
			$filter->order .= $settings['order_by'];
		}

		if ( $settings['post_id'] ) {
			$filter->post_ids = array( $settings['post_id'] );
		}

		switch ( $course_type ) {
			case 'recent':
				$filter->order_by .= 'p.post_date';
				$courses           = \LP_Course::get_courses( $filter );
				break;
			case 'popular':
				$filter->order_by = 'popular';
				$courses          = \LP_Course::get_courses( $filter );
				break;
			case 'featured':
				$filter->sort_by = 'on_feature';
				$courses         = \LP_Course::get_courses( $filter );
				break;
			default:
				$courses = \LP_Course::get_courses( $filter );
		}

		if ( empty( $courses ) ) {
			LP()->template( 'course' )->no_courses_found();
		}
		?>
		<div class="lp-archive-courses">
			<ul class="learn-press-courses" data-layout="<?php echo $settings['grid_list_type']; ?>" data-size="<?php echo absint( $settings['number_posts'] ); ?>">
				<?php
				global $post;
				foreach ( $courses as $course ) {
					$post = $course;
					setup_postdata( $course );
					learn_press_get_template( 'content-course.php' );
				}
				wp_reset_postdata();
				?>
			</ul>
		</div>
		<?php
	}

}
