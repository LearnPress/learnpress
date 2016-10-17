<?php
// Get header for our template
learn_press_get_template( 'single-course/header-content-item-only.php' );
$user = learn_press_get_course_user();
?>

	<div class="learn-press-content-item-only">
		<?php learn_press_print_messages();?>
		<?php learn_press_get_template( 'single-course/content-item.php' ); ?>
	</div>

	<script type="text/javascript">
		jQuery(function ($) {
			// Ready again!
			$(document).ready(function () {
				var windowTarget = (parent.window || window),
					data = <?php echo wp_json_encode( array_merge( $user->get_course_info2( get_the_ID() ), array( 'messageType' => 'update-course' ) ) );?>;
				$('html, body').css('opacity', 1);
				windowTarget.LP.unblockContent();
				LP.sendMessage(data, windowTarget);
			});
		});
	</script>

<?php
// Get footer for our template
learn_press_get_template( 'single-course/footer-content-item-only.php' );