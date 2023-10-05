(function($) {
    $(document).ready(function() {
        /*Assign Course*/
        $('.js-data-select-assign-course').select2({
            placeholder: $('#assign-course-message').data('placeholder-course'),
            ajax: {
                url: lpGlobalSettings.rest + `lp/v1/admin/tools/search-course`,
                dataType: 'json',
                delay: 250,
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                },
                data(params) {
                    return {
                        c_search: params.term,
                    };
                },
                processResults(data) {
                    return {
                        results: data.data.map((item) => {
                            return {
                                id: item.ID,
                                text: item.post_title,
                            };
                        }),
                    };
                },
            },
            minimumInputLength: 2,
        });
        $('input[name="assign-to"]').click(function(e) {
            $(this).closest('.assign-to-container').find('.select2-container').hide();
            $(this).closest('div').find('.select2-container').show();
        });
        $('#assign-to-user-select').select2({
            placeholder: $('#assign-course-message').data('placeholder-student'),
            ajax: {
                url: lpGlobalSettings.rest + `lp/v1/admin/tools/search-user`,
                dataType: 'json',
                delay: 250,
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                },
                data(params) {
                    return {
                        search: params.term,
                        course_id: $('.js-data-select-assign-course').val()
                    };
                },
                processResults(data) {
                    return {
                        results: data.data.map((item) => {
                            return {
                                id: item.ID,
                                text: item.display_name,
                            };
                        }),
                    };
                },
            },
            minimumInputLength: 2,
        });
        $('#assign-to-role-select').select2({
            placeholder: $('#assign-course-message').data('placeholder-role'),
            ajax: {
                url: lpGlobalSettings.rest + `lp/v1/admin/tools/search-roles`,
                dataType: 'json',
                delay: 250,
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                },
                processResults(data) {
                    return {
                        results: data.data.map((item) => {
                            return {
                                id: item.slug,
                                text: item.name,
                            };
                        }),
                    };
                },
            },
        });
        $('.assign-to-container').find('.select2-container').hide();
        $('.lp-button-assign-course').click(function(e) {
            e.preventDefault();
            let course_id = $('.js-data-select-assign-course').val(),
                assign_type = $('input[name="assign-to"]:checked').val(),
                assign_value = assign_type == 'user' ? $('#assign-to-user-select').val() : $('#assign-to-role-select').val();
            if (!course_id) {
                alert($('#assign-course-message').data('placeholder-course'));
            } else if (!assign_type) {
                alert($('#assign-course-message').data('select-type'));
            } else if (assign_value.length == 0) {
                alert($('#assign-course-message').data('select-data'));
            } else {
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: lpGlobalSettings.rest + `lp/v1/admin/tools/assign-course`,
                    beforeSend(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                    },
                    data: {
                        course_id: course_id,
                        assign_type: assign_type,
                        assign_value: assign_value
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            $('.js-data-select-assign-course').val('').select2();
                            $('#assign-to-user-select').val([]).select2();
                            $('#assign-to-role-select').val([]).select2();
                            $('.assign-to-container').find('.select2-container').hide();
                            alert(res.message);
                        } else {
							alert( res.message );
                        }
                    },
                    error: function(request, status, error) {
                        console.log(request.responseText);
                    }
                });
            }
        });
        /*Unassign Course*/
        $('.js-data-select-unassign-course').select2({
            placeholder: $('#assign-course-message').data('placeholder-course'),
            ajax: {
                url: lpGlobalSettings.rest + `lp/v1/admin/tools/search-course`,
                dataType: 'json',
                delay: 250,
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                },
                data(params) {
                    return {
                        c_search: params.term,
                    };
                },
                processResults(data) {
                    return {
                        results: data.data.map((item) => {
                            return {
                                id: item.ID,
                                text: item.post_title,
                            };
                        }),
                    };
                },
                cache: true,
            },
            minimumInputLength: 2,
        });
        $('#remove-user-select').select2({
            placeholder: $('#assign-course-message').data('placeholder-student'),
            ajax: {
                url: lpGlobalSettings.rest + `lp/v1/admin/tools/search-user`,
                dataType: 'json',
                delay: 250,
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                },
                data(params) {
                    return {
                        course_id: $('.js-data-select-unassign-course').val(),
                        remove: 1
                    };
                },
                processResults(data) {
                    // console.log( data );
                    return {
                        results: data.data.map((item) => {
                            return {
                                id: item.user_id,
                                text: item.display_name,
                            };
                        }),
                    };
                },
                cache: true,
            },
        });
        $('.lp-button-unassign-course').click(function(e) {
            e.preventDefault();
            let course_id = $('.js-data-select-unassign-course').val(),
                remove_user = $('#remove-user-select').val();
            if (!course_id) {
                alert($('#assign-course-message').data('placeholder-course'));
            } else if (remove_user.length == 0) {
                alert($('#assign-course-message').data('placeholder-student'));
            } else {
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: lpGlobalSettings.rest + `lp/v1/admin/tools/remove-user-from-course`,
                    beforeSend(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', lpGlobalSettings.nonce);
                    },
                    data: {
                        course_id: course_id,
                        remove_user: remove_user,
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            $('.js-data-select-unassign-course').val('').select2();
                            $('#remove-user-select').val([]).select2();
                            alert(res.message);
                        } else {
                            alert($('#assign-course-message').data('unassign-error'));
                        }
                    },
                    error: function(request, status, error) {
                        console.log(request.responseText);
                    }
                });
            }
        });
    });
})(jQuery);
