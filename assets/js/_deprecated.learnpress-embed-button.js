(function() {

    tinymce.PluginManager.add('embed', function( editor )
    {        
        editor.addButton('embed', {
            type: 'button',
            text: 'Embed',
            onclick: function(event) {            	
            	editor.windowManager.open({
                title: 'Your video embed link',
                body: [
                    {type: 'textbox', name: 'link', label: 'Your video embed link'}
                ],
                onsubmit: function(e) {
                    // Insert content when the window form is submitted
                    editor.insertContent('[embed_video link=' + e.data.link + ']');
                }
            });
            }
        });
    });
})();