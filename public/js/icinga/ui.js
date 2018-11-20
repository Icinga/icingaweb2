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

        /**
         * Whether the mobile menu is shown
         *
         * @type {bool}
         */
        this.mobileMenu = false;

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
                icinga.ui.fixControls();
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
                        icinga.ui.fixControls();
                        $oldLink.remove();
                    });
                    $newLink.appendTo($('head'));
                }
            });
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
            var $element = $('#' + element);

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

            if ($element.length) {
                if (typeof $element.attr('tabindex') === 'undefined') {
                    $element.attr('tabindex', -1);
                }

                $element.focus();

                if ($container && $container.length) {
                    $container.scrollTop(0);
                    $container.scrollTop($element.first().position().top);
                }
            }
        },

        moveToLeft: function () {
            var col2 = this.cutContainer($('#col2'));
            var kill = this.cutContainer($('#col1'));
            this.pasteContainer($('#col1'), col2);
            this.fixControls();
            this.icinga.behaviors.navigation.trySetActiveByUrl($('#col1').data('icingaUrl'));
        },

        cutContainer: function ($col) {
            var props = {
              'elements': $('#' + $col.attr('id') + ' > *').detach(),
              'data': {
                'data-icinga-url': $col.data('icingaUrl'),
                'data-icinga-refresh': $col.data('icingaRefresh'),
                'data-last-update': $col.data('lastUpdate'),
                'data-icinga-module': $col.data('icingaModule')
              },
              'class': $col.attr('class')
            };
            this.icinga.loader.stopPendingRequestsFor($col);
            $col.removeData('icingaUrl');
            $col.removeData('icingaRefresh');
            $col.removeData('lastUpdate');
            $col.removeData('icingaModule');
            $col.removeAttr('class').attr('class', 'container');
            return props;
        },

        pasteContainer: function ($col, backup) {
            backup['elements'].appendTo($col);
            $col.attr('class', backup['class']); // TODO: ie memleak? remove first?
            $col.data('icingaUrl', backup['data']['data-icinga-url']);
            $col.data('icingaRefresh', backup['data']['data-icinga-refresh']);
            $col.data('lastUpdate', backup['data']['data-last-update']);
            $col.data('icingaModule', backup['data']['data-icinga-module']);
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
            _this.fixControls();
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
            // one-column layouts never have any selection active
            $('#col1').removeData('icinga-actiontable-former-href');
            this.icinga.behaviors.actiontable.clearAll();
        },

        closeContainer: function($c) {
            $c.removeData('icingaUrl');
            $c.removeData('icingaRefresh');
            $c.removeData('lastUpdate');
            $c.removeData('icingaModule');
            this.icinga.loader.stopPendingRequestsFor($c);
            $c.trigger('close-column');
            $c.html('');
            this.fixControls();
        },

        layout2col: function () {
            if (! this.isOneColLayout()) { return; }
            this.icinga.logger.debug('Switching to double col');
            $('#layout').addClass('twocols');
            this.fixControls();
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
         * Initialize all TriStateCheckboxes in the given html
         */
        initializeTriStates: function ($html) {
            $('div.tristate', $html).each(function(index, item) {
                var $target  = $(item);

                // hide input boxess and remove text nodes
                $target.find("input").hide();
                $target.contents().filter(function() { return this.nodeType === 3; }).remove();

                // has three states?
                var triState = $target.find('input[value="unchanged"]').size() > 0 ? 1 : 0;

                // fetch current value from radiobuttons
                var value  = $target.find('input:checked').first().val();

                $target.append(
                  '<input class="tristate-dummy" ' +
                        ' data-icinga-old="' + value + '" data-icinga-tristate="' + triState + '" type="checkbox" ' +
                        (value === '1' ? 'checked ' : ( value === 'unchanged' ? 'indeterminate="true" ' : ' ' )) +
                  '/> <b style="visibility: hidden;" class="tristate-changed"> (changed) </b>'
                );
                if (triState) {
                  // TODO: find a better way to activate indeterminate checkboxes after load.
                  $target.append(
                    '<script type="text/javascript"> ' +
                      ' $(\'input.tristate-dummy[indeterminate="true"]\').each(function(i, el){ el.indeterminate = true; }); ' +
                    '</script>'
                  );
                }
            });
        },

        /**
         * Set the value of the given TriStateCheckbox
         *
         * @param value     {String}  The value to set, can be '1', '0' and 'unchanged'
         * @param $checkbox {jQuery}  The checkbox
         */
        setTriState: function(value, $checkbox) {
            switch (value) {
                case ('1'):
                    $checkbox.prop('checked', true).prop('indeterminate', false);
                    break;
                case ('0'):
                    $checkbox.prop('checked', false).prop('indeterminate', false);
                    break;
                case ('unchanged'):
                    $checkbox.prop('checked', false).prop('indeterminate', true);
                    break;
            }
        },

        /**
         * Toggle mobile menu
         *
         * @param {object} e Event
         */
        toggleMobileMenu: function(e) {
            var $sidebar = $('#sidebar');
            var $target = $(e.target);
            var href = $target.attr('href');
            if (href) {
                if (href !== '#') {
                    $sidebar.removeClass('expanded');
                }
            } else if (! $target.is('input')) {
                $sidebar.toggleClass('expanded');
            }
        },

        /**
         * Close mobile menu when the enter key was pressed
         *
         * @param {object} e Event
         */
        closeMobileMenu: function(e) {
            var $search = $('#search');
            if (e.which === 13 && $search.is(':focus')) {
                $('#sidebar').removeClass('expanded');
                $search.blur();
            }
        },

        initializeControls: function(container) {
            var $container = $(container);

            if ($container.parent('.dashboard').length || $('#layout').hasClass('fullscreen-layout')) {
                return;
            }

            $container.find('.controls').each(function() {
                var $controls = $(this);
                if (! $controls.next('.fake-controls').length) {
                    var $tabs = $controls.find('.tabs', $controls);
                    if ($tabs.length && $controls.children().length > 1 && ! $tabs.next('.tabs-spacer').length) {
                        $tabs.after($('<div class="tabs-spacer"></div>'));
                    }
                    var $fakeControls = $('<div class="fake-controls"></div>');
                    $fakeControls.height($controls.height()).css({
                        display: 'block'
                    });
                    $controls.css({
                        position: 'fixed'
                    }).after($fakeControls);
                }
            });

            this.fixControls($container);
        },

        fixControls: function($container) {
            var $layout = $('#layout');

            if ($layout.hasClass('fullscreen-layout')) {
                return;
            }

            if (typeof $container === 'undefined') {
                var $header = $('#header');
                var $headerLogo = $('#header-logo-container');
                var $main = $('#main');
                var $search = $('#search');
                var $sidebar = $('#sidebar');

                $header.css({ height: 'auto'});

                if ($layout.hasClass('minimal-layout')) {
                    if (! this.mobileMenu && $sidebar.length) {
                        $header.css({
                            top: $sidebar.outerHeight() + 'px'
                        });
                        $headerLogo.css({
                            display: 'none'
                        });
                        $main.css({
                            top: $header.outerHeight() + $sidebar.outerHeight()
                        });
                        $sidebar
                            .on(
                                'click',
                                this.toggleMobileMenu
                            )
                            .prepend(
                                $('<div id="mobile-menu-toggle"><button><i class="icon-menu"></i></button></div>')
                            );
                        $(window).on('keypress', this.closeMobileMenu);

                        this.mobileMenu = true;
                    }
                } else {
                    $headerLogo.css({
                        top: $header.css('height')
                    });
                    $main.css({
                        top: $header.css('height')
                    });
                    if (!! $headerLogo.length) {
                        $sidebar.css({
                            top: $headerLogo.offset().top + $headerLogo.outerHeight()
                        });
                    }

                    if (this.mobileMenu) {
                        $header.css({
                            top: 0
                        });
                        $headerLogo.css({
                            display: 'block'
                        });
                        $sidebar.removeClass('expanded').off('click', this.toggleMobileMenu);
                        $search.off('keypress', this.closeMobileMenu);
                        $('#mobile-menu-toggle').remove();

                        this.mobileMenu = false;
                    }
                }

                var _this = this;
                $('.container').each(function () {
                    _this.fixControls($(this));
                });

                return;
            }

            if ($container.parent('.dashboard').length) {
                return;
            }

            // Enable this only in case you want to track down UI problems
            //this.icinga.logger.debug('Fixing controls for ', $container);

            $container.find('.controls').each(function() {
                var $controls = $(this);
                var $fakeControls = $controls.next('.fake-controls');
                $controls.css({
                    top: $container.offsetParent().position().top,
                    width: $fakeControls.outerWidth()
                });
                $fakeControls.height($controls.height());
            });

            var $statusBar = $container.children('.monitoring-statusbar');
            if ($statusBar.length) {
                $statusBar.css({
                    left: $container.offset().left,
                    width: $container.width()
                });
                $statusBar.prev('.monitoring-statusbar-ghost').height($statusBar.outerHeight(true));
            }
        },

        toggleFullscreen: function () {
            $('#layout').toggleClass('fullscreen-layout');
            this.fixControls();
        },

        getWindowId: function () {
            if (! this.hasWindowId()) {
                return undefined;
            }
            return window.name.match(/^Icinga_([a-zA-Z0-9]+)$/)[1];
        },

        hasWindowId: function () {
            var res = window.name.match(/^Icinga_([a-zA-Z0-9]+)$/);
            return typeof res === 'object' && null !== res;
        },

        setWindowId: function (id) {
            this.icinga.logger.debug('Setting new window id', id);
            window.name = 'Icinga_' + id;
        },

        destroy: function () {
            // This is gonna be hard, clean up the mess
            this.icinga = null;
            this.debugTimer = null;
            this.timeCounterTimer = null;
        }
    };

}(Icinga, jQuery));
