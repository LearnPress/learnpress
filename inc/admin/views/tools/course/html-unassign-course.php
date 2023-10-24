<?php
/**
  * @author  ThimPress
  * @package LearnPress/Admin/Views
  * @version 4.2.4
  */

defined( 'ABSPATH' ) or die();
?>
<div id="learn-press-unassign-course" class="card">
	<h2><?php _e( 'Unassign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<?php _e( 'Remove user from a course', 'learnpress' ); ?>
	</div>
	<div class="content">
		<form id="lp-unassign-course-form">
			<!-- <fieldset class="lp-assign-course__options"> -->
				<!-- <legend><?php _e( 'Options', 'learnpress' ); ?></legend> -->
				<ul>
					<li>
						<p><b><?php _e( 'Course', 'learnpress' ); ?></b></p><select class="js-data-select-unassign-course" style="width: 100%;"></select>
					</li>
					<li>
						<div class="assign-to-user">
							<p><b><?php _e( 'User', 'learnpress' ); ?></b></p>
							<select id="remove-user-select" multiple style="width:100%"></select>
						</div>
					</li>
				</ul>
			<!-- </fieldset> -->
			<p><button class="button button-primary lp-button-unassign-course" type="button"><?php _e( 'Remove', 'learnpress' ); ?></button></p>
		</form>
	</div>
</div>