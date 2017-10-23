<?php
$course = LP_Global::course();
?>
<div id="course-item-content-footer">
    <form class="lp-form" action="<?php echo $course->get_permalink(); ?>">
    <button
       class="lp-button"><?php _e( 'Back to Course', 'learnpress' ); ?></button>
    </form>
</div>