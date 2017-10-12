<h2><?php _e( 'Static Pages', 'learnpress' ); ?></h2>

<table>
    <tr>
        <th><?php _e( 'Courses', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'learn_press_courses_page_id' ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Profile', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'learn_press_profile_page_id' ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Checkout', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'learn_press_checkout_page_id' ); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Become a Teacher', 'learnpress' ); ?></th>
        <td>
			<?php learn_press_pages_dropdown( 'learn_press_become_a_teacher_page_id' ); ?>
        </td>
    </tr>
</table>
