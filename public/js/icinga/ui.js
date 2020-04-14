/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Icinga.UI
 *
 * Our user interface
 */
(function(Icinga, $) {

    'use strict';

    Icinga.UI = function (icinga) {

        this.icinga = icinga;

        this.currentLayout = 'default';

        this.debug = false;

        this.debugTimer = null;

        this.timeCounterTimer = null;

        // detect currentLayout
        var classList = $('#layout').attr('class').split(/\s+/);
        var _this = this;
        var matched;
        $.each(classList, function(index, item) {
            if (null !== (matched = item.match(/^([a-z]+)-layout$/))) {
                var layout = matched[1];
                if (layout !== 'fullscreen') {
                    _this.currentLayout = layout;
                    // Break loop
                    return false;
                }
            }
        });
    };

    Icinga.UI.prototype = {

        initialize: function () {
            $('html').removeClass('no-js').addClass('js');
            this.enableTimeCounters();
            this.triggerWindowResize();
            this.fadeNotificationsAway();

            $(document).on('click', '#mobile-menu-toggle', this.toggleMobileMenu);
            $(document).on('keypress', '#search',{ self: this, type: 'key' }, this.closeMobileMenu);
            $(document).on('mouseleave', '#sidebar', { self: this, type: 'leave' }, this.closeMobileMenu);
            $(document).on('click', '#sidebar a', { self: this, type: 'navigate' }, this.closeMobileMenu);
        },

        fadeNotificationsAway: function() {
            var icinga = this.icinga;
            $('#notifications li')
                .not('.fading-out')
                .not('.persist')
                .addClass('fading-out')
                .delay(7000)
                .fadeOut('slow',
            function() {
                $(this).remove();
            });
        },

        toggleDebug: function() {
            if (this.debug) {
                return this.disableDebug();
            } else {
                return this.enableDebug();
            }
        },

        enableDebug: function () {
            if (this.debug === true) { return this; }
            this.debug = true;
            this.debugTimer = this.icinga.timer.register(
                this.refreshDebug,
                this,
                1000
            );
            this.fixDebugVisibility();

            return this;
        },

        fixDebugVisibility: function () {
            if (this.debug) {
                $('#responsive-debug').css({display: 'block'});
            } else {
                $('#responsive-debug').css({display: 'none'});
            }
            return this;
        },

        disableDebug: function () {
            if (this.debug === false) { return; }

            this.debug = false;
            this.icinga.timer.unregister(this.debugTimer);
            this.debugTimer = null;
            this.fixDebugVisibility();
            return this;
        },

        reloadCss: function () {
            var icinga = this.icinga;
            icinga.logger.info('Reloading CSS');
            $('link').each(function() {
                var $oldLink = $(this);
                if ($oldLink.hasAttr('type') && $oldLink.attr('type').indexOf('css') > -1) {
                    var base = location.protocol + '//' + location.host;
                    var url = icinga.utils.addUrlParams(
                        $(this).attr('href'),
                        { id: new Date().getTime() }
                    );

                    var $newLink = $oldLink.clone().attr(
                        'href',
                        base + '/' + url.replace(/^\//, '')
                    ).on('load', function() {
                        $oldLink.remove();
                    });

                    $newLink.appendTo($('head'));
                }
            });
        },

        reloadJs: function () {
            icinga.logger.info('Reloading JS');
            location.reload(true);
        },

        enableTimeCounters: function () {
            this.timeCounterTimer = this.icinga.timer.register(
                this.refreshTimeSince,
                this,
                1000
            );
            return this;
        },

        disableTimeCounters: function () {
            this.icinga.timer.unregister(this.timeCounterTimer);
            this.timeCounterTimer = null;
            return this;
        },

        /**
         * Focus the given element and scroll to its position
         *
         * @param   {string}    element         The name or id of the element to focus
         * @param   {object}    [$container]    The container containing the element
         */
        focusElement: function(element, $container) {
            var $element = element;

            if (typeof element === 'string') {
                if ($container && $container.length) {
                    $element = $container.find('#' + element);
                } else {
                    $element = $('#' + element);
                }

                if (! $element.length) {
                    // The name attribute is actually deprecated, on anchor tags,
                    // but we'll possibly handle links from another source
                    // (module etc) so that's used as a fallback
                    if ($container && $container.length) {
                        $element = $container.find('[name="' + element.replace(/'/, '\\\'') + '"]');
                    } else {
                        $element = $('[name="' + element.replace(/'/, '\\\'') + '"]');
                    }
                }
            }

            if ($element.length) {
                if (! this.isFocusable($element)) {
                    $element.attr('tabindex', -1);
                }

                $element[0].focus();

                if ($container && $container.length) {
                    if (! $container.is('.container')) {
                        $container = $container.closest('.container');
                    }

                    if ($container.css('display') === 'flex' && $container.is('.container')) {
                        var $controls = $container.find('.controls');
                        var $content = $container.find('.content');
                        $content.scrollTop($element.offsetTopRelativeTo($content) - $controls.outerHeight() - (
                            $element.outerHeight(true) - $element.innerHeight()
                        ));
                    } else {
                        $container.scrollTop($element.first().position().top);
                    }
                }
            }
        },

        isFocusable: function ($element) {
            return $element.is('*[tabindex], a[href], input:not([disabled]), button:not([disabled])' +
                ', select:not([disabled]), textarea:not([disabled]), iframe, area[href], object' +
                ', embed, *[contenteditable]');
        },

        moveToLeft: function () {
            var col2 = this.cutContainer($('#col2'));
            var kill = this.cutContainer($('#col1'));
            this.pasteContainer($('#col1'), col2);
            this.icinga.behaviors.navigation.trySetActiveAndSelectedByUrl($('#col1').data('icingaUrl'));
        },

        cutContainer: function ($col) {
            var props = {
              'elements': $('#' + $col.attr('id') + ' > *').detach(),
              'data': {
                'data-icinga-url': $col.data('icingaUrl'),
                'data-icinga-title': $col.data('icingaTitle'),
                'data-icinga-refresh': $col.data('icingaRefresh'),
                'data-last-update': $col.data('lastUpdate'),
                'data-icinga-module': $col.data('icingaModule'),
                'data-icinga-container-id': $col.data('icingaContainerId')
              },
              'class': $col.attr('class')
            };
            this.icinga.loader.stopPendingRequestsFor($col);
            $col.removeData('icingaUrl');
            $col.removeData('icingaTitle');
            $col.removeData('icingaRefresh');
            $col.removeData('lastUpdate');
            $col.removeData('icingaModule');
            $col.removeData('icingaContainerId');
            $col.removeAttr('class').attr('class', 'container');
            return props;
        },

        pasteContainer: function ($col, backup) {
            backup['elements'].appendTo($col);
            $col.attr('class', backup['class']); // TODO: ie memleak? remove first?
            $col.data('icingaUrl', backup['data']['data-icinga-url']);
            $col.data('icingaTitle', backup['data']['data-icinga-title']);
            $col.data('icingaRefresh', backup['data']['data-icinga-refresh']);
            $col.data('lastUpdate', backup['data']['data-last-update']);
            $col.data('icingaModule', backup['data']['data-icinga-module']);
            $col.data('icingaContainerId', backup['data']['data-icinga-container-id']);
        },

        triggerWindowResize: function () {
            this.onWindowResize({data: {self: this}});
        },

        /**
         * Our window got resized, let's fix our UI
         */
        onWindowResize: function (event) {
            var _this = event.data.self;

            if (_this.layoutHasBeenChanged()) {
                _this.icinga.logger.info(
                    'Layout change detected, switching to',
                    _this.currentLayout
                );
            }

            _this.refreshDebug();
        },

        /**
         * Returns whether the layout is too small for more than one column
         *
         * @returns {boolean}   True when more than one column is available
         */
        hasOnlyOneColumn: function () {
            return this.currentLayout === 'poor' || this.currentLayout === 'minimal';
        },

        layoutHasBeenChanged: function () {

            var layout = $('html').css('fontFamily').replace(/['",]/g, '');
            var matched;

            if (null !== (matched = layout.match(/^([a-z]+)-layout$/))) {
                if (matched[1] === this.currentLayout &&
                    $('#layout').hasClass(layout)
                ) {
                    return false;
                } else {
                    $('#layout').removeClass(this.currentLayout + '-layout').addClass(layout);
                    this.currentLayout = matched[1];
                    if (this.currentLayout === 'poor' || this.currentLayout === 'minimal') {
                        this.layout1col();
                    } else {
                        // layout1col() also triggers this, that's why an else is required
                        $('#layout').trigger('layout-change');
                    }
                    return true;
                }
            }
            this.icinga.logger.error(
                'Someone messed up our responsiveness hacks, html font-family is',
                layout
            );
            return false;
        },

        /**
         * Returns whether only one column is displayed
         *
         * @returns {boolean}   True when only one column is displayed
         */
        isOneColLayout: function () {
            return ! $('#layout').hasClass('twocols');
        },

        layout1col: function () {
            if (this.isOneColLayout()) { return; }
            this.icinga.logger.debug('Switching to single col');
            $('#layout').removeClass('twocols');
            this.closeContainer($('#col2'));
            $('#layout').trigger('layout-change');
            // one-column layouts never have any selection active
            $('#col1').removeData('icinga-actiontable-former-href');
            this.icinga.behaviors.actiontable.clearAll();
        },

        closeContainer: function($c) {
            $c.removeData('icingaUrl');
            $c.removeData('icingaTitle');
            $c.removeData('icingaRefresh');
            $c.removeData('lastUpdate');
            $c.removeData('icingaModule');
            this.icinga.loader.stopPendingRequestsFor($c);
            $c.trigger('close-column');
            $c.html('');
        },

        layout2col: function () {
            if (! this.isOneColLayout()) { return; }
            this.icinga.logger.debug('Switching to double col');
            $('#layout').addClass('twocols');
            $('#layout').trigger('layout-change');
        },

        getAvailableColumnSpace: function () {
            return $('#main').width() / this.getDefaultFontSize();
        },

        setColumnCount: function (count) {
            if (count === 3) {
                $('#main > .container').css({
                    width: '33.33333%'
                });
            } else if (count === 2) {
                $('#main > .container').css({
                    width: '50%'
                });
            } else {
                $('#main > .container').css({
                    width: '100%'
                });
            }
        },

        setTitle: function (title) {
            document.title = title;
            return this;
        },

        getColumnCount: function () {
            return $('#main > .container').length;
        },

        /**
         * Assign a unique ID to each .container without such
         *
         * This usually applies to dashlets
         */
        assignUniqueContainerIds: function() {
            var currentMax = 0;
            $('.container').each(function() {
                var $el = $(this);
                var m;
                if (!$el.attr('id')) {
                    return;
                }
                if (m = $el.attr('id').match(/^ciu_(\d+)$/)) {
                    if (parseInt(m[1]) > currentMax) {
                         currentMax = parseInt(m[1]);
                    }
                }
            });
            $('.container').each(function() {
                var $el = $(this);
                if (!!$el.attr('id')) {
                    return;
                }
                currentMax++;
                $el.attr('id', 'ciu_' + currentMax);
            });
        },

        refreshDebug: function () {
            if (! this.debug) {
                return;
            }

            var size = this.getDefaultFontSize().toString();
            var winWidth = $( window ).width();
            var winHeight = $( window ).height();
            var loading = '';

            $.each(this.icinga.loader.requests, function (el, req) {
                if (loading === '') {
                    loading = '<br />Loading:<br />';
                }
                loading += el + ' => ' + encodeURI(req.url);
            });

            $('#responsive-debug').html(
                '   Time: ' +
                this.icinga.utils.formatHHiiss(new Date()) +
                '<br />    1em: ' +
                size +
                'px<br />    Win: ' +
                winWidth +
                'x'+
                winHeight +
                'px<br />' +
                ' Layout: ' +
                this.currentLayout +
                loading
            );
        },

        /**
         * Refresh partial time counters
         *
         * This function runs every second.
         */
        refreshTimeSince: function () {
            $('.time-ago, .time-since').each(function (idx, el) {
                var partialTime = /(\d{1,2})m (\d{1,2})s/.exec(el.innerHTML);
                if (partialTime !== null) {
                    var minute = parseInt(partialTime[1], 10),
                        second = parseInt(partialTime[2], 10);
                    if (second < 59) {
                        ++second;
                    } else {
                        ++minute;
                        second = 0;
                    }
                    el.innerHTML = el.innerHTML.substr(0, partialTime.index) + minute.toString() + 'm '
                        + second.toString() + 's' + el.innerHTML.substr(partialTime.index + partialTime[0].length);
                }
            });

            $('.time-until').each(function (idx, el) {
                var partialTime = /(-?)(\d{1,2})m (\d{1,2})s/.exec(el.innerHTML);
                if (partialTime !== null) {
                    var minute = parseInt(partialTime[2], 10),
                        second = parseInt(partialTime[3], 10),
                        invert = partialTime[1];
                    if (invert.length) {
                        // Count up because partial time is negative
                        if (second < 59) {
                            ++second;
                        } else {
                            ++minute;
                            second = 0;
                        }
                    } else {
                        // Count down because partial time is positive
                        if (second === 0) {
                            if (minute === 0) {
                                // Invert counter
                                minute = 0;
                                second = 1;
                                invert = '-';
                            } else {
                                --minute;
                                second = 59;
                            }
                        } else {
                            --second;
                        }
                    }
                    el.innerHTML = el.innerHTML.substr(0, partialTime.index) + invert + minute.toString() + 'm '
                        + second.toString() + 's' + el.innerHTML.substr(partialTime.index + partialTime[0].length);
                }
            });
        },

        createFontSizeCalculator: function () {
            var $el = $('<div id="fontsize-calc">&nbsp;</div>');
            $('#layout').append($el);
            return $el;
        },

        getDefaultFontSize: function () {
            var $calc = $('#fontsize-calc');
            if (! $calc.length) {
                $calc = this.createFontSizeCalculator();
            }
            return $calc.width() / 1000;
        },

        /**
         * Toggle mobile menu
         *
         * @param {object} e Event
         */
        toggleMobileMenu: function(e) {
            $('#sidebar').toggleClass('expanded');
        },

        /**
         * Close mobile menu when the enter key is pressed during search or the user leaves the sidebar
         *
         * @param {object} e Event
         */
        closeMobileMenu: function(e) {
            if (e.data.self.currentLayout !== 'minimal') {
                return;
            }

            if (e.data.type === 'key') {
                if (e.which === 13) {
                    $('#sidebar').removeClass('expanded');
                    $(e.target)[0].blur();
                }
            } else {
                $('#sidebar').removeClass('expanded');
            }
        },

        toggleFullscreen: function () {
            $('#layout').toggleClass('fullscreen-layout');
        },

        getUniqueContainerId: function ($cont) {
            if (typeof $cont === 'undefined' || !$cont.length) {
                return null;
            }

            var containerId = $cont.data('icingaContainerId');
            if (typeof containerId === 'undefined') {
                /**
                 * Only generate an id if it's not for col1 or the menu (which are using the non-suffixed window id).
                 * This is based on the assumption that the server only knows about the menu and first column
                 * and therefore does not need to protect its ids. (As the menu is most likely part of the sidebar)
                 */
                if ($cont.attr('id') === 'menu' || $cont.attr('id') === 'col1' || $cont.closest('#col1').length) {
                    return null;
                }

                containerId = this.icinga.utils.generateId(6); // Random because the content may move
                $cont.data('icingaContainerId', containerId);
            }

            return containerId;
        },

        getWindowId: function () {
            if (! this.hasWindowId()) {
                return undefined;
            }
            return window.name.match(/^Icinga-([a-zA-Z0-9]+)$/)[1];
        },

        hasWindowId: function () {
            var res = window.name.match(/^Icinga-([a-zA-Z0-9]+)$/);
            return typeof res === 'object' && null !== res;
        },

        setWindowId: function (id) {
            this.icinga.logger.debug('Setting new window id', id);
            window.name = 'Icinga-' + id;
        },

        destroy: function () {
            // This is gonna be hard, clean up the mess
            this.icinga = null;
            this.debugTimer = null;
            this.timeCounterTimer = null;
        }
    };

}(Icinga, jQuery));
