<script type="text/html" id="tmpl-learn-press-search-items">
	<div class="modal-inner">
		<header>
			<input type="text" name="lp-search-term" placeholder="<# if(data.placeholder){#>{{data.placeholder}}<#}else{#><?php _e( 'Type here to search item', 'learnpress' ); ?><#}#>">
		</header>
		<article>
			<ul class="lp-list-items">
			</ul>
		</article>
		<footer>
			<input type="checkbox" class="chk-checkall" disabled="disabled" />
			<button class="lp-add-item button" disabled="disabled" data-text="<# if(data.addText){ #>{{data.addText}}<# }else{ #><?php _e( 'Add', 'learnpress' );?><# } #>">
				<# if(data.addText){ #>{{data.addText}}<# }else{ #><?php _e( 'Add', 'learnpress' );?><# } #>
			</button>
			<button class="lp-add-item close button" disabled="disabled" data-text="<# if(data.addAndCloseText){ #>{{data.addAndCloseText}}<# }else{ #><?php _e( 'Add and Close', 'learnpress' ); ?><# } #>">
				<# if(data.addAndCloseText){ #>
                                    {{data.addAndCloseText}}
                                <# }else{ #>
                                    <?php _e( 'Add and Close', 'learnpress' ); ?>
                                <# } #>
			</button>
			<button class="close-modal button" onclick="LP.MessageBox.hide();">
				<# if(data.closeText){ #>
                                    {{data.closeText}}
                                <# }else{ #>
                                    <?php _e( 'Close', 'learnpress' ); ?>
                                <# } #>
			</button>
		</footer>
	</div>
</script>
<script type="text/html" id="tmpl-learn-press-duplicate-course">
	<div id="learn-press-duplicate-course" class="modal-inner lp-modal-search">
		<header>
                        <h3><?php _e( 'Duplicate', 'learnpress' ); ?> <strong>{{ data.title }}</strong> <?php _e( 'course', 'learnpress' ); ?></h3>
		</header>
		<footer>
			<button class="lp-duplicate-course all-content button learn-press-tooltip" data-id="{{ data.id }}" data-nonce="<?php echo esc_attr( wp_create_nonce( 'lp-duplicate-course' ) ) ?>" data-text="<?php esc_attr_e( 'Duplicating ...', 'learnpress' ) ?>" data-content="<?php esc_attr_e( 'Duplicate course\'s curriculum', 'learnpress' ); ?>">
				<?php _e( 'All Content', 'learnpress' ); ?>
			</button>
			<button class="lp-duplicate-course button learn-press-tooltip" data-id="{{ data.id }}" data-nonce="<?php echo esc_attr( wp_create_nonce( 'lp-duplicate-course' ) ) ?>" data-text="<?php esc_attr_e( 'Duplicating ...', 'learnpress' ) ?>" data-content="<?php esc_attr_e( 'Duplicate course no curriculum', 'learnpress' ); ?>">
				<?php _e( 'No Content', 'learnpress' ); ?>
			</button>
			<button class="close-modal button" onclick="LP.MessageBox.hide();">
				<?php _e( 'Close', 'learnpress' ); ?>
			</button>
		</footer>
	</div>
</script>