<div class="lp-toolbar-btn" ng-class="{'lp-btn-disabled': !questionData.id}">
    <a target="_blank" data-tooltip="<?php esc_attr_e( 'Clone this question', 'learnpress' ); ?>"
       ng-click="cloneQuestion($event)"
       class="lp-btn-icon dashicons dashicons-admin-page learn-press-tooltip"></a>
</div>