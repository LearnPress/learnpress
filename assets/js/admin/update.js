;(function ($) {
    'use strict';

    var Package = function (data) {
        this.data = data;

        var currentIndex = -1,
            currentVersion = null,
            currentPackage = null,
            versions = Object.keys(this.data);

        this.reset = function (current) {
            current = (current === undefined || current > versions.length - 1 || current < 0 ) ? 0 : current;
            currentIndex = current;
            currentVersion = versions[current];
            currentPackage = this.data[currentVersion];

            return currentPackage;
        }

        this.next = function () {
            if (currentIndex >= versions.length - 1) {
                return false;
            }

            currentIndex++;
            this.reset(currentIndex);

            return currentPackage;
        }

        this.prev = function () {
            if (currentIndex <= 0) {
                return false;
            }

            currentIndex--;
            this.reset(currentIndex);

            return currentPackage;
        }

        this.currentVersion = function () {
            return currentVersion;
        }

        this.hasPackage = function () {
            return versions.length
        }

        this.getPercentCompleted = function () {
            return (currentIndex) / versions.length;
        }

        this.getTotal = function () {
            return versions.length;
        }

        if (!this.data) {
            return;
        }

    }
    var UpdaterSettings = {
        el: '#learn-press-updater',
        data: {
            packages: null,
            status: '',
            force: false,
        },
        watch: {
            packages: function (newPackages, oldPackages) {
                if (newPackages) {

                }
            }
        },
        mounted: function () {
            $(this.$el).show();
        },
        methods: {
            getUpdatePackages: function (callback) {
                var that = this;
                $.ajax({
                    url: lpGlobalSettings.admin_url,
                    data: {
                        'lp-ajax': 'get-update-packages',
                        force: this.force,
                        _wpnonce: lpGlobalSettings._wpnonce
                    },
                    success: function (res) {
                        var packages = LP.parseJSON(res);
                        that.packages = new Package(packages);
                        callback && callback.call(that)
                    }
                })
            },
            start: function (e, force) {
                this.packages = null;
                this.force = force;
                this.getUpdatePackages(function () {
                    if (this.packages.hasPackage()) {
                        var p = this.packages.next();
                        this.status = 'updating';
                        this.doUpdate(p);
                    }
                });
            },
            getPackages: function () {
                return this.packages ? this.packages.data : {};
            },
            hasPackage: function () {
                return !$.isEmptyObject(this.getPackages());
            },
            updateButtonClass: function () {
                return {
                    'disabled': this.status === 'updating'
                }
            },
            doUpdate: function (p, i) {
                var that = this;

                p = p ? p : this.packages.next();
                i = i ? i : 1;

                if (p) {
                    $.ajax({
                        url: lpGlobalSettings.admin_url,
                        data: {
                            'lp-ajax': 'do-update-package',
                            package: p,
                            version: this.packages.currentVersion(),
                            _wpnonce: lpGlobalSettings._wpnonce,
                            force: this.force,
                            i: i
                        },
                        success: function (res) {
                            var response = LP.parseJSON(res),
                                $status = $(that.$el).find('.updater-progress-status');
                            if (response.done === 'yes') {
                                that.update(that.packages.getPercentCompleted() * 100);
                                that.doUpdate();
                            } else {
                                var newWidth = that.packages.getPercentCompleted() * 100;
                                if (response.percent) {
                                    var stepWidth = 1 / that.packages.getTotal();
                                    newWidth += (stepWidth * response.percent);
                                }

                                that.update(newWidth);
                                that.doUpdate(p, ++i);
                            }
                        },
                        error: function () {
                            that.doUpdate(p, i);
                        }
                    });

                } else {
                    that.update(100).addClass('completed');
                    setTimeout(function (x) {
                        x.status = 'completed';
                    }, 2000, this);
                }
            },
            update: function (value) {
                return $(this.$el).find('.updater-progress-status').css('width', value + '%').attr('data-value', parseInt(value));
            }
        }
    };

    function init() {

        window.lpGlobalSettings = window.lpGlobalSettings || {};

        if($('#learn-press-updater').length) {
            var Updater = new Vue(UpdaterSettings);
        }
    }

    $(document).ready(init);

})(jQuery);
