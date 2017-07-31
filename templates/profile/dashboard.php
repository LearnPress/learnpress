<?php $user = learn_press_get_current_user(); ?>
<div id="user-dashboard" class="user-dashboard">
	<div id="ud-header" class="ud-header">
		<div class="ud-cover">
			<div class="ud-avatar">
				<?php echo $user->get_profile_picture(); ?>
			</div>
		</div>
		<div class="ud-top-menu">
			<ul class="ud-top-tabs">
				<li><a href="">Courses Manager</a> </li>
				<li><a href="">Statistic</a> </li>
				<li><a href="">Gradebook</a> </li>
				<li><a href="">Settings</a> </li>
			</ul>
		</div>
	</div>
	<div id="ud-content" class="ud-content">

	</div>
	<div id="ud-footer" class="ud-footer">

	</div>
</div>