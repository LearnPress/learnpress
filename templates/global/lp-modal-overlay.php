<?php
/**
 * Template for displaying modal overlay.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/login.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  1.0.0
 */
?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLiveLabel">Modal title</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="modal-body">
            <p class="message">Message modal</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="lp-button btn-yes"><?php esc_html_e('Yes', 'learnpress');?></button>
            <button type="button" class="lp-button btn-no"><?php esc_html_e('No', 'learnpress');?></button>
        </div>
    </div>
</div>
