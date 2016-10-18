<?php
// Get header for our template
learn_press_get_template( 'single-course/header-content-item-only.php' );
$user   = learn_press_get_course_user();
$course = learn_press_get_the_course();
$item   = LP()->global['course-item'];
$data   = array(
	'messageType'          => 'update-course',
	'completed_items_text' => __( '%d of %d items', 'learnpress' ),
);
global $lp_query;
if ( !empty( $_REQUEST['done-action'] ) ) {
	$url = $course->get_item_link( $item->id );
	switch ( $_REQUEST['done-action'] ) {
		case 'start-quiz':
		case 'retake-quiz':
			if ( !empty( $lp_query->query_vars['question'] ) ) {
				$url = trailingslashit( $course->get_item_link( $item->id ) ) . $lp_query->query_vars['question'] . '/';
			}
			break;
	}
	$data['setUrl'] = $url;
} else {
	if ( $user->has_quiz_status( 'started', $item->id, $course->id ) ) {
		if ( !empty( $lp_query->query_vars['question'] ) ) {
			$url = trailingslashit( $course->get_item_link( $item->id ) ) . $lp_query->query_vars['question'] . '/';
		}
	}
}
$data = array_merge( $user->get_course_info2( get_the_ID() ), $data );
?>

	<div class="learn-press-content-item-only">
		<?php learn_press_print_messages(); ?>
		<?php learn_press_get_template( 'single-course/content-item.php' ); ?>
	</div>

	<script type="text/javascript">
		jQuery(function ($) {
			// Ready again!
			$(document).ready(function () {
				var windowTarget = (parent.window || window),
					data = <?php echo wp_json_encode( $data );?>;
				$('html, body').css('opacity', 1);
				windowTarget.LP.unblockContent();
				setTimeout(function () {
					$('.learn-press-message').each(function () {
						$(this).fadeOut();
					})
				}, 3000);
				LP.sendMessage(data, windowTarget);
			});
		});
	</script>

<?php
// Get footer for our template
learn_press_get_template( 'single-course/footer-content-item-only.php' );