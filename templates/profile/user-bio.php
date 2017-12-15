<?php
/**
 * Template for displaying user's BIO in profile.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

$user = LP_Profile::instance()->get_user();
?>
<div class="user-bio">
	<?php echo $user->get_description(); ?>
</div>
