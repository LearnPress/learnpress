<?php
$course = learn_press_get_course( get_the_ID() );
$user   = learn_press_get_current_user();
?>
<script type="text/template" id="learn-press-template-curriculum-popup">
	<div id="course-curriculum-popup">
		<div id="popup-sidebar">
			<?php //learn_press_get_template( 'single-course/curriculum.php' ); ?>
		</div>
		<div id="popup-main">
			<div id="popup-header">
				<div class="popup-menu"></div>
				<h3 class="popup-title"><?php echo $course->get_title(); ?></h3>
				<a class="popup-close"></a>
			</div>
			<div id="popup-content">
				<div id="popup-content-inner"></div>
			</div>
			<div id="popup-footer">
				<!--
				<?php if ( $prev_item = $course->get_next_item( array( 'dir' => 'prev' ) ) ): ?>
					<a class="footer-control prev-item" data-id="<?php echo $prev_item; ?>" href="<?php echo $course->get_item_link( $prev_item ); ?>"><?php echo get_the_title( $prev_item ); ?></a>
				<?php endif; ?>
				<?php if ( $next_item = $course->get_next_item() ): ?>
					<a class="footer-control next-item" data-id="<?php echo $next_item; ?>" href="<?php echo $course->get_item_link( $next_item ); ?>"><?php echo get_the_title( $next_item ); ?></a>
				<?php endif; ?>

				<?php if ( $user->has( 'finished-course', $course->id ) ): ?>
					<?php if ( $count = $user->can( 'retake-course', $course->id ) ): ?>
						<button
							class="button button-retake-course"
							data-block-content="yes"
							data-course_id="<?php echo esc_attr( $course->id ); ?>"
							data-security="<?php echo esc_attr( wp_create_nonce( sprintf( 'learn-press-retake-course-%d-%d', $course->id, $user->id ) ) ); ?>">
							<?php echo esc_html( sprintf( __( 'Retake course (+%d)', 'learnpress' ), $count ) ); ?>
						</button>
					<?php endif; ?>
				<?php else: ?>
					<button
						class="button-finish-course"
						data-id="<?php esc_attr_e( $course->id ); ?>"
						data-block-content="yes"
						data-security="<?php esc_attr_e( wp_create_nonce( 'learn-press-finish-course-' . $course->id . '-' . $user->id ) ); ?>">
						<?php esc_html_e( 'Finish course', 'learnpress' ); ?>
					</button>
				<?php endif; ?>-->
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="learn-press-template-course-prev-item">
	<a class="footer-control prev-item hide-if-js" data-id="{{data.id}}" href="{{data.url}}">{{data.title}}</a>
</script>

<script type="text/template" id="learn-press-template-course-next-item">
	<a class="footer-control next-item hide-if-js" data-id="{{data.id}}" href="{{data.url}}">{{data.title}}</a>
</script>
<script type="text/template" id="learn-press-template-block-content">
	<div id="learn-press-block-content" class="popup-block-content">
		<span></span>
	</div>
</script>