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
                    console.log('Get items: ', that.items.length);
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
        itemsKey: '_nothin_here',
        chunkSize: 500
    });

    window.LP_Sync_Data = {
        syncs: [],
        syncing: 0,
        init: function () {
            this.syncs = [];

            if (!this.get_syncs()) {
                return;
            }

            this.reset();
            $('input[name^="lp-repair"]').prop('disabled', true);
            var that = this,
                syncing = 0,
                totalSyncs = this.syncs.length,
                syncCallback = function ($sync) {

                    if ($sync.is_completed()) {
                        syncing++;
                        console.log('Done ', $sync.id);
                        if (syncing >= totalSyncs) {
                            $('input[name^="lp-repair"]').prop('disabled', false);

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
            var $input = $('input[name^="lp-repair"]').eq(sync);
            $input.closest('li').css('opacity', '0.5')
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
        sync_course_orders: Sync_Course_Orders,
        sync_user_orders: Sync_User_Orders,
        sync_user_courses: Sync_User_Courses,
        sync_course_final_quiz: Sync_Course_Final_Quiz,
        sync_remove_older_data: Sync_Remove_Older_Data
    }

    $(document).on('click', '.lp-button-repair', function () {
        LP_Sync_Data.init();
    });

})(jQuery);