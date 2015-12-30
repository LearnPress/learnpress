<script type="text/html" id="tmpl-learn-press-search-items">
	<div class="modal-inner">
		<header>
			<input type="text" name="lp-search-term" placeholder="<# if(data.placeholder){#>{{data.placeholder}}<#}else{#><?php _e('Type here to search the item', 'learn_press');?><#}#>">
		</header>
		<article>
			<ul class="lp-list-items">
			</ul>
		</article>
		<footer>
			<button class="lp-add-item button" disabled="disabled" data-text="<# if(data.addText){ #>{{data.addText}}<# }else{ #><?php _e( 'Add', 'learn_press' );?><# } #>">
				<# if(data.addText){ #>{{data.addText}}<# }else{ #><?php _e( 'Add', 'learn_press' );?><# } #>
			</button>
			<button class="close-modal button" onclick="LearnPress.MessageBox.hide();"><# if(data.closeText){ #>{{data.closeText}}<# }else{ #><?php _e( 'Close', 'learn_press' );?><# } #></button>
		</footer>
	</div>
</script>