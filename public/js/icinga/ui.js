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
    };

    Icinga.UI.prototype = {

        initialize: function () {
            $('html').removeClass('no-js').addClass('js');
            this.enableTimeCounters();
            this.triggerWindowResize();
            this.fadeNotificationsAway();
        },

        fadeNotificationsAway: function()
        {
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

        enableDebug: function () {
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
            $('link').each(function() {
                var $oldLink = $(this);
                if ($oldLink.attr('type').indexOf('css') > -1) {
                    var $newLink = $oldLink.clone().attr(
                        'href',
                        icinga.utils.addUrlParams(
                            $(this).attr('href'),
                            { id: new Date().getTime() }
                        )
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

        moveToLeft: function () {
            var col2 = this.cutContainer($('#col2'));
            var kill = this.cutContainer($('#col1'));
            this.pasteContainer($('#col1'), col2);
            this.fixControls();
        },

        cutContainer: function ($col) {
            var props = {
              'elements': $('#' + $col.attr('id') + ' > div').detach(),
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

        scrollContainerToAnchor: function ($container, anchorName) {
            // TODO: Generic issue -> we probably should escape attribute value selectors!?
            var $anchor = $("a[name='" + anchorName.replace(/'/, '\\\'') + "']", $container);
            if ($anchor.length) {
                $container.scrollTop(0);
                $container.scrollTop($anchor.first().position().top);
                this.icinga.logger.debug('Scrolling ', $container, ' to ', anchorName);
            } else {
                this.icinga.logger.info('Anchor "' + anchorName + '" not found in ', $container);
            }
        },

        triggerWindowResize: function () {
            this.onWindowResize({data: {self: this}});
        },

        /**
         * Our window got resized, let's fix our UI
         */
        onWindowResize: function (event) {
            var self = event.data.self;

            if (self.layoutHasBeenChanged()) {
                self.icinga.logger.info(
                    'Layout change detected, switching to',
                    self.currentLayout
                );
            }
            self.fixControls();
            self.refreshDebug();
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

        layout1col: function () {
            if (! $('#layout').hasClass('twocols')) { return; }
            var $col2 = $('#col2');
            icinga.logger.debug('Switching to single col');
            $('#layout').removeClass('twocols');
            $col2.removeData('icingaUrl');
            $col2.removeData('icingaRefresh');
            $col2.removeData('lastUpdate');
            $col2.removeData('icingaModule');
            this.icinga.loader.stopPendingRequestsFor($col2);
            $col2.html('');
            this.fixControls();
        },

        layout2col: function () {
            if ($('#layout').hasClass('twocols')) { return; }
            icinga.logger.debug('Switching to double col');
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

        prepareContainers: function () {
            var icinga = this.icinga;
            $('.container').each(function(idx, el) {
                icinga.events.applyHandlers($(el));
                icinga.ui.initializeControls($(el));
            });
            /*
            $('#icinga-main').attr(
                'icingaurl',
                window.location.pathname + window.location.search
            );
            */
        },

        /**
         * Prepare all multiselectable tables for multi-selection by
         * removing the regular text selection.
         */
        prepareMultiselectTables: function () {
            var $rows = $('table.multiselect tr[href]');
            $rows.find('td').attr('unselectable', 'on')
                .css('user-select', 'none')
                .css('-webkit-user-select', 'none')
                .css('-moz-user-select', 'none')
                .css('-ms-user-select', 'none');
        },

        /**
         * Add the given table-row to the selection of the closest
         * table and deselect all other rows of the closest table.
         *
         * @param $tr {jQuery}  The selected table row.
         * @returns {boolean}   If the selection was changed.
         */
        setTableRowSelection: function ($tr) {
            var $table   = $tr.closest('table.multiselect');
            if ($tr.hasClass('active')) {
                return false;
            }
            $table.find('tr[href].active').removeClass('active');
            $tr.addClass('active');
            return true;
        },

        /**
         * Toggle the given table row to "on" when not selected, or to "off" when
         * currently selected.
         *
         * @param $tr {jQuery}  The table row.
         * @returns {boolean}   If the selection was changed.
         */
        toogleTableRowSelection: function ($tr) {
            // multi selection
            if ($tr.hasClass('active')) {
                $tr.removeClass('active');
            } else {
                $tr.addClass('active');
            }
            return true;
        },

        /**
         * Add a new selection range to the closest table, using the selected row as
         * range target.
         *
         * @param $tr {jQuery}  The target of the selected range.
         * @returns {boolean}   If the selection was changed.
         */
        addTableRowRangeSelection: function ($tr) {
            var $table = $tr.closest('table.multiselect');
            var $rows  = $table.find('tr[href]'),
                from, to;
            var selected = $tr.first().get(0);
            $rows.each(function(i, el) {
                if ($(el).hasClass('active') || el === selected) {
                    if (!from) {
                        from = el;
                    }
                    to = el;
                }
            });
            var inRange = false;
            $rows.each(function(i, el){
                if (el === from) {
                    inRange = true;
                }
                if (inRange) {
                    $(el).addClass('active');
                }
                if (el === to) {
                    inRange = false;
                }
            });
            return false;
        },

        /**
         * Focus the given table by deselecting all selections on all other tables.
         *
         * Focusing a table is important for environments with multiple tables like
         * the dashboard. It should only be possible to select rows at one table at a time,
         * when a user selects a row on a table all rows that are not child of the given table
         * will be removed from the selection.
         *
         * @param table {htmlElement}   The table to focus.
         */
        focusTable: function (table) {
            $('table').filter(function(){ return this !== table; }).find('tr[href]').removeClass('active');
        },

        refreshDebug: function () {

            var size = this.getDefaultFontSize().toString();
            var winWidth = $( window ).width();
            var winHeight = $( window ).height();
            var loading = '';

            $.each(this.icinga.loader.requests, function (el, req) {
                if (loading === '') {
                    loading = '<br />Loading:<br />';
                }
                loading += el + ' => ' + req.url;
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

        refreshTimeSince: function () {

            $('.timesince').each(function (idx, el) {
                var m = el.innerHTML.match(/^(-?\d+)m\s(-?\d+)s/);
                if (m !== null) {
                    var nm = parseInt(m[1]);
                    var ns = parseInt(m[2]);
                    if (ns < 59) {
                        ns++;
                    } else {
                        ns = 0;
                        nm++;
                    }
                    $(el).html(nm + 'm ' + ns + 's');
                }
            });

            $('.timeunless').each(function (idx, el) {
                var m = el.innerHTML.match(/^(-?\d+)m\s(-?\d+)s/);
                if (m !== null) {
                    var nm = parseInt(m[1]);
                    var ns = parseInt(m[2]);
                    var signed = '';
                    var sec = 0;

                    if (nm < 0) {
                        signed = '-';    
                        nm = nm * -1;
                        sec = nm * 60 + ns;
                        sec++;
                    } else if (nm == 0 && ns == 0) {
                        signed = '-';    
                        sec = 1;
                    } else if (nm == 0 && m[1][0] == '-') {
                        signed = '-';    
                        sec = ns;
                        sec++;
                    } else if (nm == 0 && m[1][0] != '-') {
                        sec = ns;
                        sec--;
                    } else {
                        signed = '';    
                        sec = nm * 60 + ns;
                        sec--;
                    }    

                    nm = Math.floor(sec/60);
                    ns = sec - nm * 60;

                    $(el).html(signed + nm + 'm ' + ns + 's');
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
            var self = this;
            $('div.tristate', $html).each(function(index, item) {
                var target   = item;
                var $target  = $(target);
                var value    = $target.find('input:checked').first().val();
                var triState = value === 'unchanged' ? true : false;
                var name     = $('input', target).first().attr('name');
                var old      = value;

                var getStateDescription = function(value) {
                    if (value === 'unchanged') {
                        return '(mixed values)';
                    }
                    return '';
                };

                $target.empty();
                $target.parent().parent()
                    .find('label')
                    .append('&#160;&#160;<span class="tristate-changed"></span>');
                $target.append(
                    '<input name="' + name + '" ' +
                        'class="tristate" type="checkbox" ' +
                        'data-icinga-old="' + old + '" ' +
                        'data-icinga-tristate="' + triState + '" ' +
                        'data-icinga-value="' + value + '" ' +
                        ( value === 'unchanged' ? 'indeterminate=1 ' : ' ' ) +
                        ( value === '1' ? 'checked ' : ' ' ) +
                    '></input>' +
                    '<div class="tristate-status">' + getStateDescription(value) + '</div>'
                );
            });
        },

        /**
         * Set the value of the given TriStateCheckbox
         *
         * @param value     {String}  The value to set, can be '1', '0' and 'unchanged'
         * @param $checkbox {jQuery}  The checkbox
         */
        updateTriState: function(value, $checkbox)
        {
            console.log($checkbox);
            switch (value) {
                case ('1'):
                    console.log('checked true; indeterminate: false');
                    $checkbox.prop('checked', true).prop('indeterminate', false);
                    break;
                case ('0'):
                    console.log('checked false; indeterminate: false');
                    $checkbox.prop('checked', false).prop('indeterminate', false);
                    break;
                case ('unchanged'):
                    console.log('checked false; indeterminate: true');
                    $checkbox.prop('checked', false).prop('indeterminate', true);
                    break;
            }
        },

        initializeControls: function (parent) {

            var self = this;

            $('.controls', parent).each(function (idx, el) {
                var $el = $(el);

                if (! $el.next('.fake-controls').length) {

                    var newdiv = $('<div class="fake-controls"></div>');
                    newdiv.css({
                        height: $el.css('height')
                    });
                    $el.after(newdiv);
                }
            });

            this.fixControls(parent);
        },

        fixControls: function ($parent) {

            var self = this;

            if ('undefined' === typeof $parent) {

                $('#header').css({height: 'auto'});
                $('#main').css({top: $('#header').css('height')});
                $('#sidebar').css({top: $('#header').height() + 'px'});
                $('#header').css({height: $('#header').height() + 'px'});
                $('#inner-layout').css({top: $('#header').css('height')});
                $('.container').each(function (idx, container) {
                    self.fixControls($(container));
                });

                return;
            }

            // Enable this only in case you want to track down UI problems
            // self.icinga.logger.debug('Fixing controls for ', $parent);

            $('.controls', $parent).each(function (idx, el) {
                var $el = $(el);
                var $fake = $el.next('.fake-controls');
                var y = $parent.scrollTop();

                $el.css({
                    position : 'fixed',
                    top      : $parent.offset().top,
                    // Firefox gives 1px too much depending on total width.
                    // TODO: find a better solution for -1
                    width    : ($fake.width() - 1) + 'px'
                });

                $fake.css({
                    height  : $el.css('height'),
                    display : 'block'
                });
            });
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
