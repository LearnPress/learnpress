<?php
/**
 * Template for displaying a message in profile dashboard if user is logged in.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

defined( 'ABSPATH' ) or exit;
$profile = LP_Global::profile();

learn_press_display_message( sprintf( __( 'Please <a href="%s">login</a> to see your profile content', 'learnpress' ), $profile->get_login_url() ) );
?>