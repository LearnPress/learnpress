<?php
/**
 * Template for displaying setup form of static pages while setting up LP
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit;

$settings = LP()->settings();
?>
<h2><?php _e( 'Static Pages', 'learnpress' ); ?></h2>

<table>
    <tr>
        <th><?php _e( 'Courses', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'settings[pages][courses_page_id]', $settings->get( 'courses_page_id' ) ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Profile', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'settings[pages][profile_page_id]', $settings->get( 'profile_page_id' ) ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Checkout', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'settings[pages][checkout_page_id]', $settings->get( 'checkout_page_id' ) ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Become a Teacher', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'settings[pages][become_a_teacher_page_id]', $settings->get( 'become_a_teacher_page_id' ) ); ?>
        </td>
    </tr>
</table>
