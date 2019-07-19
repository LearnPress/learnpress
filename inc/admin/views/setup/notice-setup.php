<div id="notice-install" class="lp-notice notice notice-warning">
    <p><?php _e( '<strong>LearnPress has just successfully installed.</strong>', 'learnpress' ); ?></p>
    <p>
        <a class="button"
           href="<?php echo admin_url( 'index.php?page=lp-setup' ); ?>"><?php _e( 'Run setup wizard', 'learnpress' ); ?></a>
<!--        <button class="button" id="skip-notice-install">--><?php //_e( 'Skip', 'learnpress' ); ?><!--</button>-->
        <button class="button" data-dismiss-notice="skip-setup-wizard"><?php _e( 'Skip', 'learnpress' ); ?></button>
    </p>
</div>