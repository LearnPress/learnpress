<div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
    <a data-tooltip="<?php esc_attr_e( 'Remove this question', 'learnpress' ); ?>"
       class="lp-btn-icon dashicons dashicons-trash learn-press-tooltip" ng-click="removeQuestion($event)"></a>
    <ul>
        <li><a class="learn-press-tooltip"
               data-tooltip="<?php esc_attr__( 'Delete permanently this question from Questions Bank', 'learnpress' ); ?>"
               ng-click="removeQuestion($event)" data-delete-permanently="yes">
				<?php esc_html_e( 'Delete permanently', 'learnpress' ); ?>
            </a>
        </li>
    </ul>
</div>