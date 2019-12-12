<?php
/**
 * Template for displaying user socials
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;
$socials = LP_Profile::instance()->get_user()->get_profile_socials();
?>
<div class="lp-user-profile-socials">
	<?php
	print_r( join( "\n", $socials ) );
	?>
</div>
