<?php
$course = LP_Global::course();
?>
<div id="course-item-content-footer">
    <form class="lp-form form-button" action="<?php echo $course->get_permalink(); ?>">
        <button
                class="lp-button"><?php _e( 'Back to Course', 'learnpress' ); ?></button>
    </form>
	<?php if ( 1 == 0 ) { ?>
        <form class="lp-form form-button" method="post">
            <button
                    class="lp-button"><?php _e( 'Finish', 'learnpress' ); ?></button>
        </form>
	<?php } ?>
</div>