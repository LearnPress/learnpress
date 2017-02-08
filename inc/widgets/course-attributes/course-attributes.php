<?php
if ( !class_exists( 'LP_Widget_Course_Attributes' ) ) {
	/**
	 * Class LP_Widget_Course_Attribute
	 */
	class LP_Widget_Course_Attributes extends LP_Widget {
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( 'Course attributes', 'learnpress' )
				),
				array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}x-title",
					'type' => 'text',
					'std'  => __( 'Course attributes', 'learnpress' )
				),
				array(
					'name'    => __( 'Radio', 'learnpress' ),
					'id'      => "{$prefix}radio",
					'type'    => 'radio',
					'std'     => __( '3', 'learnpress' ),
					'options' => array(
						'1' => 'One',
						'2' => 'Two',
						'3' => 'Three',
						'4' => 'Four',
						'5' => 'Five'
					)
				)
			);
			add_filter( 'learn_press_widget_display_content', 'learn_press_is_course' );
			parent::__construct();
		}

		public function show() {
			$postId     = get_the_ID();
			$attributes = learn_press_get_course_attributes( $postId );
			if ( !$attributes ) {
				return;
			}
			$start = microtime( true );
			?>
			<ul>
				<?php
				foreach ( $attributes as $attribute ) {
					$taxonomy = get_term_by( 'slug', $attribute['name'], LP_COURSE_ATTRIBUTE );
					?>
					<li>
						<h4><?php echo $taxonomy->name; ?></h4>
						<?php
						$values = wp_get_object_terms( $postId, LP_COURSE_ATTRIBUTE . '-' . $attribute['name'] );
						if ( !$values ) {
							continue;
						}
						?>
						<ul>
							<?php foreach ( $values as $value ) { ?>
								<li><?php echo $value->name; ?></li>
							<?php } ?>
						</ul>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
			echo microtime( true ) - $start;
		}
	}
}