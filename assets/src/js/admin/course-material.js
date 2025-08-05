import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import Sortable from 'sortablejs';
document.addEventListener('DOMContentLoaded', function() {
    const $ = window.jQuery;
    const postID = document.getElementById('current-material-post-id').value,
        max_file_size = document.getElementById('material-max-file-size').value,
        accept_file = document.querySelector('.lp-material--field-upload').getAttribute('accept').split(','),
        can_upload = document.getElementById('available-to-upload'),
        add_btn = document.getElementById('btn-lp--add-material'),
        group_template = document.getElementById('lp-material--add-material-template'),
        material__group_container = document.getElementById('lp-material--group-container'),
        material_tab = document.getElementById('lp-material-container'),
        material_save_btn = document.getElementById('btn-lp--save-material');
    let ajaxUrl = lpDataAdmin.lpAjaxUrl;
    const getResponse = async (ele, postID) => {
        const elementMaterial = document.querySelector('.lp-material--table tbody');
        try {
            const url = `${ lpDataAdmin.lp_rest_url }lp/v1/material/item-materials/${ postID }`;
            fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': lpGlobalSettings.nonce,
                        'Content-Type': 'application/json',
                    },
                })
                .then((response) => response.json())
                .then((response) => {
                    const {
                        data,
                        status
                    } = response;
                    if (status !== 'success') {
                        console.error(response.message);
                        return;
                    }

                    if (data && data.items && data.items.length > 0) {
                        const materials = data.items;
                        if (ele.querySelector('.lp-skeleton-animation')) {
                            ele.querySelector('.lp-skeleton-animation').remove();
                        }
                        for (let i = 0; i < materials.length; i++) {
                            insertRow(elementMaterial, materials[i]);
                        }
                    }
                })
                .catch((err) => console.log(err));
        } catch (error) {
            console.log(error.message);
        }
    };
    const insertRow = (parent, data) => {
        if (!parent) {
            return;
        }
        const delete_btn_text = document.getElementById('delete-material-row-text').value;
        parent.insertAdjacentHTML(
            'beforeend',
            `<tr data-id="${ data.file_id }" data-sort="${ data.orders }" >
              <td class="sort"><span class="dashicons dashicons-menu"></span> ${ data.file_name }</td>
              <td>${ capitalizeFirstChar( data.method ) }</td>
              <td><a href="javascript:void(0)" class="delete-material-row" data-id="${ data.file_id }">${ delete_btn_text }</a></td>
            </tr>`
        );
    };
    const capitalizeFirstChar = (str) => str.charAt(0).toUpperCase() + str.substring(1);
    //load material data from API
    getResponse(material_tab, postID);

    //add material group field
    add_btn.addEventListener('click', function(e) {
        const can_upload_data = parseInt(this.getAttribute('can-upload'));
        const groups = material__group_container.querySelectorAll('.lp-material--group').length;
        if (groups >= can_upload_data) {
            return false;
        }
        material__group_container.insertAdjacentHTML('afterbegin', group_template.innerHTML);
    });
    //switch input when change method between "upload" and "external"
    material_tab.addEventListener('change', function(event) {
        const target = event.target;
        if (target.classList.contains('lp-material--field-method')) {
            const method = target.value;
            const upload_field_template = document.getElementById('lp-material--upload-field-template').innerHTML,
                external_field_template = document.getElementById('lp-material--external-field-template').innerHTML;
            switch (method) {
                case 'upload':
                    target.parentNode.insertAdjacentHTML('afterend', upload_field_template);
                    target.closest('.lp-material--group').querySelector('.lp-material--external-wrap').remove();
                    break;
                case 'external':
                    target.parentNode.insertAdjacentHTML('afterend', external_field_template);
                    target.closest('.lp-material--group').querySelector('.lp-material--upload-wrap').remove();
                    break;
            }
        }
        if (target.classList.contains('lp-material--field-upload')) {
            if (target.value && target.files.length > 0) {
                if (!accept_file.includes(target.files[0].type)) {
                    alert('This file is not allowed! Please choose another file!');
                    target.value = '';
                } else if (target.files[0].size > max_file_size * 1024 * 1024) {
                    alert(`This file size is greater than ${ max_file_size }MB! Please choose another file!`);
                    target.value = '';
                }
            }
        }
    });
    // Dynamic click action ...
    material_tab.addEventListener('click', function(event) {
        const target = event.target;
        if (target.classList.contains('lp-material--delete') && target.nodeName == 'BUTTON') {
            target.closest('.lp-material--group').remove();
        } else if (target.classList.contains('lp-material-save-field')) {
            // save a file
            let material = target.closest('.lp-material--group');
            material = singleNode(material);
            lpSaveMaterial(material, true, target);
        }
        return false;
    });

    //save all material files
    material_save_btn.addEventListener('click', function(event) {
        const materials = material__group_container.querySelectorAll('.lp-material--group');
        if (materials.length > 0) {
            lpSaveMaterial(materials, false, material_save_btn);
        }
    });

    function lpSaveMaterial(materials, is_single = false, target) {
        if (materials.length > 0) {
            let material_data = [];
            let formData = new FormData(),
                send_request = true;

            materials.forEach(function(ele, index) {
                const label = ele.querySelector('.lp-material--field-title').value,
                    method = ele.querySelector('.lp-material--field-method').value,
                    external_field = ele.querySelector('.lp-material--field-external-link'),
                    upload_field = ele.querySelector('.lp-material--field-upload');
                let file, link;
                if (!label) {
                    send_request = false;
                }
                switch (method) {
                    case 'upload':
                        if (upload_field.value) {
                            file = upload_field.files[0].name;
                            link = '';
                            formData.append('file[]', upload_field.files[0]);
                        } else {
                            send_request = false;
                        }
                        break;
                    case 'external':
                        link = external_field.value;
                        file = '';
                        if (!link) {
                            send_request = false;
                        }
                        break;
                }
                material_data.push({label, method, file, link });
            });

            if (!send_request) {
                alert('Enter file title, choose file or enter file link!');
            } else {
                // console.log(material_data);
                material_data = JSON.stringify(material_data);
                const data = {
                    item_id: postID,
                    material_data: material_data
                }
                formData.append('data', JSON.stringify(data));
                formData.append('nonce', lpDataAdmin.nonce);
                formData.append('lp-load-ajax', 'save_materials');
                const dataSend = {
                    method: 'POST',
                    headers: {},
                    body: formData,
                };
                target.classList.add('loading');

                fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                    }) // wrapped
                    .then((response) => response.json())
                    .then((response) => {
                        // console.log( data );
                        if (!is_single) {
                            material__group_container.innerHTML = '';
                        } else {
                            materials[0].remove();
                        }
                        const { message, data, status } = response;
                        showToast(message, status);

                        if (status === 'success') {
                            const materialItems = data.items;
                            if (materialItems.length > 0) {
                                const material_table = document.querySelector('.lp-material--table');
                                const thead = material_table.querySelector('thead');
                                const tbody = material_table.querySelector('tbody');

                                thead.classList.remove('hidden');
                                for (let i = 0; i < materialItems.length; i++) {
                                    const row = materialItems[i];
                                    insertRow(tbody, row);
                                }
                                can_upload.innerText = parseInt(can_upload.innerText) - materialItems.length;
                                add_btn.setAttribute('can-upload', can_upload.innerText);
                            }
                        }
                    })
                    .finally(() => {
                        target.classList.remove('loading');
                    })
                    .catch((err) => console.log(err));
            }
        }
    }
    //delete material
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('delete-material-row') && target.nodeName == 'A') {
            const rowID = target.getAttribute('data-id'), //material file ID
                message = document.getElementById('delete-material-message').value; //Delete message content
            if (confirm(message)) {
                const dataSend = {
                    action: 'delete_material',
                    file_id: rowID
                };
                const callBack = {
                    success: (response) => {
                        const { message, status, data } = response;

                        showToast(message, status);

                        if (status === 'success') {
                            target.closest('tr').remove();
                            can_upload.innerText = parseInt( can_upload.innerText ) + 1;
                            add_btn.setAttribute( 'can-upload', can_upload.innerText );
                        }
                    },
                    error: (error) => {
                        showToast(error, 'error');
                    },
                    completed: () => {}
                };
                window.lpAJAXG.fetchAJAX( dataSend, callBack );
            }
        }
    });
    const singleNode = ((nodeList) => (node) => {
        const layer = { // define our specific case
            0: {
                value: node,
                enumerable: true
            },
            length: {
                value: 1
            },
            item: {
                value(i) {
                    return this[+i || 0];
                },
                enumerable: true,
            },
        };
        return Object.create(nodeList, layer); // put our case on top of true NodeList
    })(document.createDocumentFragment().childNodes); // scope a true NodeList
    const tbodyHandler = document.querySelector( '.lp-material--table tbody' );
    const sortMaterialRows = new Sortable(tbodyHandler, {
        animation  : 150,
        handle: '.sort',
        onEnd : () => {
            const items = tbodyHandler.querySelectorAll( 'tr' ),
                dataSort = [];
            items.forEach( (ele, idx) => {
                dataSort.push({
                    file_id: parseInt( ele.dataset.id ),
                    orders: idx + 1
                });
            } );
            const dataSend = {
                item_id: postID,
                action: 'update_material_orders',
                sort_arr: JSON.stringify( dataSort )
            };
            const callBack = {
                success: (response) => {
                    const { message, status, data } = response;
                    showToast(message, status);
                },
                error: (error) => {
                    showToast(error, 'error');
                },
                completed: () => {}
            };
            window.lpAJAXG.fetchAJAX( dataSend, callBack );
        }
    });
    const argsToastify = {
    	text: '',
    	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
    	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
    	className: `${ lpDataAdmin.toast.classPrefix }`,
    	close: lpDataAdmin.toast.close == 1,
    	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
    	duration: lpDataAdmin.toast.duration,
    };
    const showToast = (message, status = 'success') => {
        const toastify = new Toastify({
            ...argsToastify,
            text: message,
            className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
        });
        toastify.showToast();
    };
});