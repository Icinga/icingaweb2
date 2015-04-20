/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.UI
 *
 * Our user interface
 */
(function(Icinga, $) {

    'use strict';

    // Stores the icinga-data-url of the last focused table.
    var focusedTableDataUrl = null;

    // The stored selection data, useful for preserving selections over
    // multiple reload-cycles.
    var selectionData = null;

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
            this.icinga.behaviors.navigation.setActiveByUrl($('#col1').data('icingaUrl'));
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
            this.disableCloseButtons();
        },

        closeContainer: function($c) {
            $c.removeData('icingaUrl');
            $c.removeData('icingaRefresh');
            $c.removeData('lastUpdate');
            $c.removeData('icingaModule');
            this.icinga.loader.stopPendingRequestsFor($c);
            $c.html('');
            this.fixControls();
        },

        layout2col: function () {
            if (! this.isOneColLayout()) { return; }
            this.icinga.logger.debug('Switching to double col');
            $('#layout').addClass('twocols');
            this.fixControls();
            this.enableCloseButtons();
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
         * Add the given table-row to the selection of the closest
         * table and deselect all other rows of the closest table.
         *
         * @param $tr {jQuery}  The selected table row.
         * @returns {boolean}   If the selection was changed.
         */
        setTableRowSelection: function ($tr) {
            var $table   = $tr.closest('table.multiselect');
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
         * Read the data from a whole set of selections.
         *
         * @param $selections   {jQuery}    All selected rows in a jQuery-selector.
         * @param keys          {Array}     An array containing all valid keys.
         * @returns {Array} An array containing an object with the data for each selection.
         */
        getSelectionSetData: function($selections, keys) {
            var selections = [];
            var icinga = this.icinga;

            // read all current selections
            $selections.each(function(ind, selected) {
                selections.push(icinga.ui.getSelectionData($(selected), keys, icinga));
            });
            return selections;
        },

        getSelectionKeys: function($selection)
        {
            var d = $selection.data('icinga-multiselect-data') && $selection.data('icinga-multiselect-data').split(',');
            return d || [];
        },

        /**
         * Read the data from the given selected object.
         *
         * @param $selection {jQuery}   The selected object.
         * @param keys       {Array}    An array containing all valid keys.
         * @param icinga     {Icinga}   The main icinga object.
         * @returns {Object}    An object containing all key-value pairs associated with this selection.
         */
        getSelectionData: function($selection, keys, icinga)
        {
            var url    = $selection.attr('href');
            var params = this.icinga.utils.parseUrl(url).params;
            var tuple  = {};
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                if (params[key]) {
                    tuple[key] = params[key];
                }
            }
            return tuple;
        },

        /**
         * Convert a set of selection data to a single query.
         *
         * @param selectionData {Array} The selection data generated from getSelectionData
         * @returns {String}    The formatted and uri-encoded query-string.
         */
        selectionDataToQuery: function (selectionData) {
            var queries = [];

            // create new url
            if (selectionData.length < 2) {
                this.icinga.logger.error('Something went wrong, we should never multiselect just one row');
            } else {
                $.each(selectionData, function(i, el){
                    var parts = []
                    $.each(el, function(key, value) {
                        parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    });
                    queries.push('(' + parts.join('&') + ')');
                });
            }
            return '(' + queries.join('|') + ')';
        },

        /**
         * Create a single query-argument (not compatible to selectionDataToQuery)
         *
         * @param data
         * @returns {string}
         */
        selectionDataToQueryComp: function(data) {
            var queries = [];
            $.each(data, function(key, value){
                queries.push(key + '=' + encodeURIComponent(value));
            });
            return queries.join('&');
        },

        /**
         * Store a set of selection-data to preserve it accross page-reloads
         *
         * @param data {Array|String|Null}  The selection-data be an Array of Objects,
         *  containing the selection data (when multiple rows where selected), a
         * String containing a single url (when only a single row was selected) or
         * Null when nothing was selected.
         */
        storeSelectionData: function(data) {
            selectionData = data;
        },

        /**
         * Load the last stored set of selection-data
         *
         * @returns {Array|String|Null}   May be an Array of Objects, containing the selection data
         * (when multiple rows where selected), a String containing a single url
         * (when only a single row was selected) or Null when nothing was selected.
         */
        loadSelectionData: function() {
            this.provideSelectionCount();
            return selectionData;
        },

        /**
         * Set the selections row count hint info
         */
        provideSelectionCount: function() {
            var $count = $('.selection-info-count');

            if (typeof selectionData === 'undefined' || selectionData === null) {
                $count.text(0);
                return;
            }

            if (typeof selectionData === 'string') {
                $count.text(1);
            } else if (selectionData.length > 1) {
                $count.text(selectionData.length);
            } else {
                $count.text(0);
            }
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
            var n = $(table).closest('div.container').attr('data-icinga-url');
            focusedTableDataUrl = n;
        },

        /**
         * Return the URL of the last focused table container.
         *
         * @returns {String}    The data-icinga-url of the last focused table, which should be unique in each site.
         */
        getFocusedContainerDataUrl: function() {
            return focusedTableDataUrl;
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

                // todo remove after replace timeSince
                var mp = el.innerHTML.match(/^(.*?)(-?\d+)d\s(-?\d+)h/);
                if (mp !== null) {
                    return true;
                }

                var m = el.innerHTML.match(/^(.*?)(-?\d+)(.+\s)(-?\d+)(.+)/);
                if (m !== null) {
                    var nm = parseInt(m[2]);
                    var ns = parseInt(m[4]);
                    if (ns < 59) {
                        ns++;
                    } else {
                        ns = 0;
                        nm++;
                    }
                    $(el).html(m[1] + nm + m[3] + ns + m[5]);
                }
            });

            $('.timeuntil').each(function (idx, el) {

                // todo remove after replace timeUntil
                var mp = el.innerHTML.match(/^(.*?)(-?\d+)d\s(-?\d+)h/);
                if (mp !== null) {
                    return true;
                }

                var m = el.innerHTML.match(/^(.*?)(-?\d+)(.+\s)(-?\d+)(.+)/);
                if (m !== null) {
                    var nm = parseInt(m[2]);
                    var ns = parseInt(m[4]);
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
                    } else if (nm == 0 && m[2][0] == '-') {
                        signed = '-';
                        sec = ns;
                        sec++;
                    } else if (nm == 0 && m[2][0] != '-') {
                        sec = ns;
                        sec--;
                    } else {
                        signed = '';
                        sec = nm * 60 + ns;
                        sec--;
                    }

                    nm = Math.floor(sec/60);
                    ns = sec - nm * 60;

                    $(el).html(m[1] + signed + nm + m[3] + ns + m[5]);
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
        setTriState: function(value, $checkbox)
        {
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

        initializeControls: function (parent) {
            if ($(parent).closest('.dashboard').length) {
                return;
            }

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

        disableCloseButtons: function () {
            $('a.close-toggle').hide();
        },

        enableCloseButtons: function () {
            $('a.close-toggle').show();
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

            if ($parent.closest('.dashboard').length) {
                return;
            }

            // Enable this only in case you want to track down UI problems
            // self.icinga.logger.debug('Fixing controls for ', $parent);

            $('.controls', $parent).each(function (idx, el) {
                var $el = $(el);

                if ($el.closest('.dashboard').length) {
                    return;
                }

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
