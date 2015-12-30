;(function($){
	var $doc = $(document);
	function parseJSON( response ){
		var matches = response.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/),
			json = {};

		if (matches && matches[1]) {
			try {
				json = JSON.parse(matches[1]);
			}catch(e){
				LearnPress.log(e);
			}
		}
		return json;
	}
	function doRepairDatabase(){
		$('.lp-update-message').show();
		//$('#button-repair-database').attr('disabled', true);
		$.ajax({
			url: ajaxurl,
			data: {
				action: 'lp_repair_database'
			},
			type: 'post',
			dataType: 'html',
			success: function(response){
				response = parseJSON(response);
				if(response.result == 'success') {
					$('.lp-update-message').html(response.message);
				}
			}
		});
	}

	function doRollbackDatabase(){
		$('.lp-update-message').html('Processing...').show();
		//$('#button-repair-database').attr('disabled', true);
		$.ajax({
			url: ajaxurl,
			data: {
				action: 'lp_rollback_database'
			},
			type: 'post',
			dataType: 'html',
			success: function(response){
				response = parseJSON(response);
				if(response.result == 'success') {
					$('.lp-update-message').html(response.message);
				}
			}
		});
	}

	$doc.ready(function(){
		$doc.on( 'click', '#button-repair-database', function(e){
			e.preventDefault();
			doRepairDatabase();
		}).on( 'click', '#button-rollback-database', function(e){
			e.preventDefault();
			doRollbackDatabase();
		});
	});
})(jQuery);