
<?php
global $post;
$postId = $post ? $post->ID : 0;
?>
<div class="learn-press-course-attributes">
	<?php
	$attributes        = learn_press_get_attributes();
	$course_attributes = learn_press_get_course_attributes( $postId );
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
