<?php
/**
 * Template for printing js templates
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 2.1.5
 */
if ( learn_press_is_course() ) {
	$course = learn_press_get_course( get_the_ID() );
	$user   = learn_press_get_current_user();
	?>
	<script type="text/template" id="learn-press-template-curriculum-popup">
		<div id="course-curriculum-popup" class="sidebar-hide">
			<div id="popup-sidebar">
			</div>
			<div id="popup-main">
				<div id="popup-header">
					<div class="popup-menu"><span class="sidebar-hide-btn dashicons dashicons-arrow-left-alt2"></span>
					</div>
					<h3 class="popup-title">
						<span class="sidebar-show-btn dashicons dashicons-menu"></span><?php echo $course->get_title(); ?>
					</h3>
					<a class="popup-close"></a>
				</div>
				<div id="popup-content">
					<div id="popup-content-inner"></div>
				</div>
				<div id="popup-footer">

				</div>
			</div>
		</div>
	</script>

	<script type="text/template" id="learn-press-template-course-prev-item">
		<a class="footer-control prev-item hide-if-js button-load-item" data-id="{{data.id}}" href="{{data.url}}"><span>&larr;</span>{{data.title}}</a>
	</script>

	<script type="text/template" id="learn-press-template-course-next-item">
		<a class="footer-control next-item hide-if-js button-load-item" data-id="{{data.id}}" href="{{data.url}}">{{data.title}}<span>&rarr;</span></a>
	</script>

<?php } ?>

<script type="text/template" id="learn-press-template-block-content">
	<div id="learn-press-block-content" class="popup-block-content">
		<span></span>
	</div>
</script>