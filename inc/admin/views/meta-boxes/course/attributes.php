
<?php
global $post;
$postId = $post ? $post->ID : 0;
?>
<div class="learn-press-course-attributes">
	<?php
	$attributes        = learn_press_get_attributes();
	$course_attributes = learn_press_get_course_attributes( $postId );
	$course_attributes_order = get_post_meta( $postId, 'lp_course_attributes_order' );

	if ($course_attributes_order) {
	    foreach ($course_attributes_order[0] as $item) {
            $course_attributes_ordered[$item] = $course_attributes[$item];
//            var_dump($item);
        }
    }

//    var_dump($course_attributes_ordered);

	if ( $attributes ) {
		?>
		<ul class="course-attribute-taxonomy">
			<?php foreach ( $attributes as $attribute ) { ?>
				<?php
				$classes = array( 'button add-attribute' );
				if ( $course_attributes && !empty( $course_attributes[$attribute->slug] ) ) {
					$classes[] = "disabled";
				}

				?>
				<li class="<?php echo join( ' ', $classes ); ?>" data-taxonomy="<?php echo $attribute->slug; ?>"><?php echo $attribute->name; ?></li>
			<?php } ?>
		</ul>

		<ul class="course-attributes">
			<?php
			if ( $course_attributes ):
                if(isset($course_attributes_ordered))  $course_attributes = $course_attributes_ordered;

				foreach ( $course_attributes as $attribute ) {
					include learn_press_get_admin_view( 'meta-boxes/course/html-course-attribute' );
				}
			endif;
			?>
		</ul>

		<button class="button button-primary" type="button" id="save-attributes"><?php _e( 'Save attributes', 'learnpres' ); ?></button>
		<?php
	} else {
		printf( '<p class="description">%s <a class="button button-primary" href="%s">%s</a></p>',
			__( 'The is no attribute. Please add course attribute first', 'learnpress' ),
			esc_url( admin_url( 'edit-tags.php?taxonomy=course_attribute&post_type=lp_course' ) ),
			__( 'Add now', 'learnpress' )
		);
	}
	?>
</div>
