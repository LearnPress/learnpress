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
				<# if(data.addAndCloseText){ #>{{data.addAndCloseText}}
					<# }else{ #><?php _e( 'Add and Close', 'learnpress' ); ?>
						<# } #>
			</button>
			<button class="close-modal button" onclick="LearnPress.MessageBox.hide();"><# if(data.closeText){ #>{{data.closeText}}<# }else{ #><?php _e( 'Close', 'learnpress' );?><# } #></button>
		</footer>
	</div>
</script>