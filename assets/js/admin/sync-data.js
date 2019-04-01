;(function ($) {
    var Sync_Base = {
        id: 'sync-base',
        syncing: false,
        items: false,
        completed: false,
        callback: null,
        methodGetItems: '',
        itemsKey: '',
        chunkSize: 50,
        sync: function (callback) {
            if (this.syncing) {
                return;
            }

            this.callback = callback;

            if (this.items === false) {
                this.get_items();
            } else {
                if (!this.dispatch()) {
                    this.completed = true;
                    this.callToCallback();
                    return;
                }
            }

            this.syncing = true;
        },
        init: function () {
            this.syncing = false;
            this.items = false;
            this.completed = false;
        },
        is_completed: function () {
            return this.completed;
        },
        dispatch: function () {
            var that = this,
                items = this.items ? this.items.splice(0, this.chunkSize) : false;
            if (!items || items.length === 0) {
                return false;
            }
            $.ajax({
                url: '',
                data: {
                    'lp-ajax': this.id,
                    sync: items
                },
                method: 'post',
                success: function (response) {
                    response = LP.parseJSON(response)
                    that.syncing = false;
                    if (response.result !== 'success') {
                        that.completed = true;
                    }
                    that.callToCallback();
                    if (that.is_completed()) {
                        return;
                    }

                    that.sync(that.callback);
                }
            });

            return true;
        },
        callToCallback: function () {
            this.callback && this.callback.call(this);
        },
        get_items: function () {
            var that = this;
            $.ajax({
                url: '',
                data: {
                    'lp-ajax': this.id,
                    sync: this.methodGetItems
                },
                success: function (response) {
                    that.syncing = false;
                    response = LP.parseJSON(response);
                    if (response[that.itemsKey]) {
                        that.items = response[that.itemsKey];
                        that.sync(that.callback);
                    } else {
                        that.completed = true;
                        that.items = [];
                        that.callToCallback();
                    }
                }
            });
        }
    };

    var Sync_Course_Orders = $.extend({}, Sync_Base, {
        id: 'sync-course-orders',
        methodGetItems: 'get-courses',
        itemsKey: 'courses'
    });

    var Sync_User_Courses = $.extend({}, Sync_Base, {
        id: 'sync-user-courses',
        methodGetItems: 'get-users',
        itemsKey: 'users',
        chunkSize: 500
    });

    var Sync_User_Orders = $.extend({}, Sync_Base, {
        id: 'sync-user-orders',
        methodGetItems: 'get-users',
        itemsKey: 'users',
        chunkSize: 500
    });

    var Sync_Course_Final_Quiz = $.extend({}, Sync_Base, {
        id: 'sync-course-final-quiz',
        methodGetItems: 'get-courses',
        itemsKey: 'courses',
        chunkSize: 500
    });

    var Sync_Remove_Older_Data = $.extend({}, Sync_Base, {
        id: 'sync-remove-older-data',
        methodGetItems: 'remove-older-data',
        itemsKey: '_nothing_here',
        chunkSize: 500
    });

    var Sync_Calculate_Course_Results = $.extend({}, Sync_Base, {
        id: 'sync-calculate-course-results',
        methodGetItems: 'get-users',
        itemsKey: 'users',
        chunkSize: 1
    });


    window.LP_Sync_Data = {
        syncs: [],
        syncing: 0,
        options: {},
        start: function (options) {
            this.syncs = [];
            this.options = $.extend({
                onInit: function () {
                },
                onStart: function () {

                },
                onCompleted: function () {

                },
                onCompletedAll: function () {

                }
            }, options || {});

            if (!this.get_syncs()) {
                return;
            }
            this.reset();
            this.options.onInit.call(this);
            var that = this,
                syncing = 0,
                totalSyncs = this.syncs.length,
                syncCallback = function ($sync) {

                    if ($sync.is_completed()) {
                        syncing++;
                        that.options.onCompleted.call(that, $sync)
                        if (syncing >= totalSyncs) {

                            that.options.onCompletedAll.call(that)
                            return;
                        }
                        that.sync(syncing, syncCallback)
                    }
                };
            this.sync(syncing, syncCallback);
        },
        reset: function () {
            for (var sync in this.syncs) {
                try {
                    this[this.syncs[sync]].init();
                } catch (e) {
                }
            }
        },
        sync: function (sync, callback) {
            var that = this,
                $sync = this[this.syncs[sync]];
            that.options.onStart.call(that, $sync);
            $sync.sync(function () {
                callback.call(that, $sync)
            })
        },
        get_syncs: function () {
            var syncs = $('input[name^="lp-repair"]:checked').serializeJSON()['lp-repair'];
            if (!syncs) {
                return false;
            }

            for (var sync in syncs) {
                if (syncs[sync] !== 'yes') {
                    continue;
                }

                sync = sync.replace(/[-]+/g, '_');

                if (!this[sync]) {
                    continue;
                }

                this.syncs.push(sync);
            }

            return this.syncs;
        },
        get_sync: function (id) {
            id = id.replace(/[-]+/g, '_');
            return this[id];
        },
        sync_course_orders: Sync_Course_Orders,
        sync_user_orders: Sync_User_Orders,
        sync_user_courses: Sync_User_Courses,
        sync_course_final_quiz: Sync_Course_Final_Quiz,
        sync_remove_older_data: Sync_Remove_Older_Data,
        sync_calculate_course_results: Sync_Calculate_Course_Results
    }

    $(document).ready(function () {
        function initSyncs() {
            var $chkAll = $('#learn-press-check-all-syncs'),
                $chks = $('#learn-press-syncs').find('[name^="lp-repair"]');

            $chkAll.on('click', function () {
                $chks.prop('checked', this.checked)
            });

            $chks.on('click', function () {
                $chkAll.prop('checked', $chks.filter(':checked').length === $chks.length);
            })
        }

        initSyncs();
    }).on('click', '.lp-button-repair', function () {
        function getInput(sync) {
            return $('ul#learn-press-syncs').find('input[name*="' + sync + '"]')
        }

        LP_Sync_Data.start({
            onInit: function () {
                $('ul#learn-press-syncs').children().removeClass('syncing synced');
                $('.lp-button-repair').prop('disabled', true);
            },
            onStart: function ($sync) {
                getInput($sync.id).closest('li').addClass('syncing');
            },
            onCompleted: function ($sync) {
                getInput($sync.id).closest('li').removeClass('syncing').addClass('synced');
            },
            onCompletedAll: function () {
                $('ul#learn-press-syncs').children().removeClass('syncing synced');
                $('.lp-button-repair').prop('disabled', false);
            }
        });
    });

})(jQuery);