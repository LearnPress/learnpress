<?php
/**
 *
 */
$wizard = LP_Setup_Wizard::instance();
?>

<div id="main">
    <div class="lp-setup-nav">
        <ul class="lp-setup-steps">
			<?php foreach ( $steps as $key => $step ) { ?>
                <li class="<?php echo $wizard->get_current_step() == $key ? 'active' : ''; ?>">
                    <span><?php echo $step['title']; ?></span>
                </li>
			<?php } ?>
        </ul>
        <div class="lp-setup-progress">
            <div class="active"
                 style="width: <?php echo intval( ( $wizard->get_step_position() + 1 ) / sizeof( $steps ) * 100 ); ?>%;"></div>
        </div>
    </div>
    <form id="learn-press-setup-form" class="lp-setup-content" name="lp-setup" method="post">
        <!--        <ul>-->
        <!--			--><?php //foreach ( $steps as $key => $step ) { ?>
        <!--                <li class="content-->
		<?php //echo $wizard->get_current_step() == $key ? ' active' : ''; ?><!--">-->
        <!--					--><?php //call_user_func( $step['callback'] ); ?>
        <!--                </li>-->
        <!--			--><?php //} ?>
        <!--        </ul>-->
		<?php

		$step = $wizard->get_current_step( false );
		call_user_func( $step['callback'] );
		?>
        <div class="buttons">
			<?php if ( ! $wizard->is_first_step() && ! $wizard->is_last_step() && ! ( array_key_exists( 'skip_button', $step ) && $step['skip_button'] === false ) ) { ?>
                <a class="button button-skip" href="<?php echo $wizard->get_next_url(); ?>">
					<?php
					_e( 'Skip this step', 'learnpress' );
					?>
                </a>
			<?php } ?>
			<?php if ( ! $wizard->is_first_step() && ! ( array_key_exists( 'back_button', $step ) && $step['back_button'] === false ) ) { ?>
                <a class="button button-prev" href="<?php echo $wizard->get_prev_url(); ?>">
					<?php
					if ( ! empty( $step['next_button'] ) ) {
						echo $step['back_button'];
					} else {
						_e( 'Back', 'learnpress' );
					}
					?>
                </a>
			<?php } ?>
			<?php if ( ! $wizard->is_last_step() && ! ( array_key_exists( 'next_button', $step ) && $step['next_button'] === false ) ) { ?>
                <a class="button button-next" href="<?php echo $wizard->get_next_url(); ?>">
					<?php
					if ( ! empty( $step['next_button'] ) ) {
						echo $step['next_button'];
					} else {
						_e( 'Continue', 'learnpress' );
					}
					?>
                </a>
			<?php } else { ?>
                <a class="button button-finish">
					<?php _e( 'Finish', 'learnpress' ); ?>
                </a>
			<?php } ?>
        </div>
    </form>
</div>