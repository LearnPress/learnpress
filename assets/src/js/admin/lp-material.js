document.addEventListener("DOMContentLoaded", function() {
    let postID = document.getElementById( 'current-material-post-id' ).value;
    //add material group field
    document.getElementById( 'btn-lp--add-material' ).addEventListener( 'click', function( e ) {
        let group_template = document.getElementById( 'lp-material--add-material-template' ).innerHTML;
        document.getElementById( 'lp-material--group-container' ).insertAdjacentHTML( 'beforeend', group_template );
    } );
    //switch input when change method between "upload" and "external"
    document.getElementById( 'downloadable_material_data' ).addEventListener( 'change', function( event ) {
        let target = event.target;
        if ( target.classList.contains( 'lp-material--field-method' ) ) {
            let method = target.value;
            let upload_field_template = document.getElementById( 'lp-material--upload-field-template' ).innerHTML,
                external_field_template = document.getElementById( 'lp-material--external-field-template' ).innerHTML;
            switch ( method ) {
                case 'upload' :
                    target.parentNode.insertAdjacentHTML( 'afterend', upload_field_template );
                    target.closest( '.lp-material--group' ).querySelector( '.lp-material--external-wrap' ).remove();
                    break;
                case 'external' :
                    target.parentNode.insertAdjacentHTML( 'afterend', external_field_template );
                    target.closest( '.lp-material--group' ).querySelector( '.lp-material--upload-wrap' ).remove();
                    break;
            }
            // console.log(target.parentNode)
        }
    } );
    document.getElementById( 'downloadable_material_data' ).addEventListener( 'click', function( event ) {
        let target = event.target;
        if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName == 'BUTTON' ) {
            target.closest( '.lp-material--group' ).remove();
        }
        return false;
    } );
    //save material
    document.getElementById( 'btn-lp--save-material' ).addEventListener( 'click', function(event) {
        let materials = document.getElementById( 'lp-material--group-container' ).querySelectorAll( '.lp-material--group' );
        let material_data = [];
            
        if ( materials.length > 0 ) {
            let formData = new FormData(), send_request = true;
            formData.append( 'action', '_lp_save_materials' );
            materials.forEach( function ( ele, index ) {
                let label = ele.querySelector( '.lp-material--field-title' ).value,
                    method = ele.querySelector( '.lp-material--field-method' ).value,
                    external_field = ele.querySelector( '.lp-material--field-external-link' ),
                    upload_field = ele.querySelector( '.lp-material--field-upload' ), file, link;
                if ( ! label ){
                    send_request = false;
                }
                switch ( method ) {
                    case 'upload' :
                        if ( upload_field.value ) {
                            file = upload_field.files[0].name;
                            link = '';
                            formData.append( 'file[]', upload_field.files[0] );
                        } else {
                            send_request = false;
                        }
                        break;
                    case 'external' :
                        link = external_field.value;
                        file = '';
                        if( ! link )
                            send_request = false;
                        break;
                }
                material_data.push( { 'label': label, 'method': method, 'file':file, 'link':link } );
            } );
            if ( !send_request ) {
                alert( 'Enter file title, choose file or enter file link!' )
            } else {
                console.log(material_data);
                material_data = JSON.stringify( material_data );
                let url = lpGlobalSettings.ajax;
                
                formData.append( 'data', material_data );
                formData.append( 'post_id', postID );
                formData.append( 'nonce', lpGlobalSettings.nonce );
                fetch( url, {
                    method: 'POST',
                    body: formData,
                } ) // wrapped
                    .then( res => res.text() )
                    .then( data => console.log( data ) )
                    .catch( err => console.log( err ) );    
            }
        }
        // console.log( data );
    } );
    //delete material
    document.addEventListener( 'click', function (e) {
        let target = e.target;
        if ( target.classList.contains( 'delete-material-row' ) && target.nodeName == 'A' ) {
            let rowID = target.getAttribute( 'data-id' ),//material file ID
                message = document.getElementById( 'delete-material-message' ).value;//Delete message content
            if ( confirm( message ) ) {
                let formData = new FormData();
                formData.append( 'action', '_lp_delete_material' );
                formData.append( 'nonce', lpGlobalSettings.nonce );
                formData.append( 'row_id', rowID );
                fetch( lpGlobalSettings.ajax, {
                    method: 'POST',
                    body: formData,
                } )
                    .then( res => res.text() )
                    .then( data => {
                        data = JSON.parse( data );
                        if ( data.data.delete ) {
                            target.closest( 'tr' ).remove();
                        }
                    } )
                    .catch( err => console.log( err ) );
            }
        }
    } );
});