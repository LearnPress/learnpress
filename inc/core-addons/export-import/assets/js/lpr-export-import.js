;(function($){

var $doc 				= $(document),
	$body				= $(document.body),
	$import_upload_form = null;

function _ready(){

	var $add_new_h2 = $('body.post-type-lpr_course .add-new-h2'),
		$import_h2 = $('<a href="" class="add-new-h2">Import</a>');
	
	$import_upload_form = $('#lpr-import-upload-form').insertAfter( $add_new_h2.parent() );
	$import_h2
		.insertAfter($add_new_h2)
		.click(function(evt){
			evt.preventDefault();
            if( !$import_upload_form.is(':visible') ){
                $import_h2.hide();
            }else{
                $import_h2.show();
            }
			$import_upload_form.slideToggle(function(){

			});
		});
    $('a', $import_upload_form).click(function(evt){
        evt.preventDefault();
        $import_h2.trigger('click');
    });
}

$doc.ready( function(){ setTimeout( _ready, 500) });

})(jQuery)