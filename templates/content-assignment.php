<?php
/**
 * Template for displaying the content of single assignment
 */
?>

<?php
learn_press_prevent_access_directly();
?>

<?php do_action( 'learn_press_before_single_assignment' );?>

<div itemscope id="assignment-<?php the_ID(); ?>" <?php learn_press_assignment_class(); ?>>

    <?php do_action( 'learn_press_before_single_assignment_summary' );?>
    <div class="assignment-summary">

    <?php do_action( 'learn_press_single_assignment_summary' ); ?>

    </div>
    <?php do_action( 'learn_press_after_single_assignment_summary' );?>


</div>

<?php do_action( 'learn_press_after_single_assignment' );?>