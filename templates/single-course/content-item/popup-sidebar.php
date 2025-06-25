<?php
/**
 * Template for displaying course curriculum in popup
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.2
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit;
?>

<div id="popup-sidebar">
	<form method="post" class="search-course">
		<input
			type="text"
			name="s" autocomplete="off"
			placeholder="<?php echo esc_attr_x( 'Search for course content', 'search course input placeholder', 'learnpress' ); ?>"
		/>
		<button
			name="submit"
			aria-label="<?php echo esc_html_x( 'Search for course content', 'Search for course content', 'learnpress' ); ?>">
			<i class="lp-icon-search"></i>
		</button>
		<button type="button" class="clear"></button>
	</form>

	<?php
	$courseModel          = CourseModel::find( get_the_ID(), true );
	$userModel            = UserModel::find( get_current_user_id(), true );
	$singleCourseTemplate = SingleCourseTemplate::instance();
	echo $singleCourseTemplate->html_curriculum( $courseModel, $userModel );
	?>
</div>
