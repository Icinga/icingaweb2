/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.ActionTable
 *
 * A multi selection that distincts between the table rows using the row action URL filter
 */
(function(Icinga, $) {

    "use strict";

    /**
     * Remove one leading and trailing bracket and all text outside those brackets
     *
     * @param   str     {String}
     * @returns         {string}
     */
    var stripBrackets = function (str) {
        return str.replace(/^[^\(]*\(/, '').replace(/\)[^\)]*$/, '');
    };

    /**
     * Parse the filter query contained in the given url filter string
     *
     * @param   filterString    {String}
     *
     * @returns                 {Array}   An object containing each row filter
     */
    var parseSelectionQuery = function(filterString) {
        var selections = [];
        $.each(stripBrackets(filterString).split('|'), function(i, row) {
            var tuple = {};
            $.each(stripBrackets(row).split('&'), function(i, keyValue) {
                var s = keyValue.split('=');
                tuple[s[0]] = decodeURIComponent(s[1]);
            });
            selections.push(tuple);
        });
        return selections;
    };

    /**
     * Handle the selection of an action table
     *
     * @param   table   {HTMLElement}   The table
     * @param   icinga  {Icinga}
     *
     * @constructor
     */
    var Selection = function(table, icinga) {
        this.$el = $(table);
        this.icinga = icinga;
        this.col = this.$el.closest('div.container').attr('id');

        if (this.hasMultiselection()) {
            if (! this.getMultiselectionKeys().length) {
                icinga.logger.error('multiselect table has no data-icinga-multiselect-data');
            }
            if (! this.getMultiselectionUrl()) {
                icinga.logger.error('multiselect table has no data-icinga-multiselect-url');
            }
        }
    };

    Selection.prototype = {

        /**
         * The container id in which this selection happens
         */
        col: null,

        /**
         * Return all rows as jQuery selector
         *
         * @returns         {jQuery}
         */
        rows: function() {
            return this.$el.find('tr');
        },

        /**
         * Return all row action links as jQuery selector
         *
         * @returns         {jQuery}
         */
        rowActions: function() {
            return this.$el.find('tr[href]');
        },

        /**
         * Return all selected rows as jQuery selector
         *
         * @returns         {jQuery}
         */
        selections: function() {
            return this.$el.find('tr.active');
        },

        /**
         * If this selection allows selecting multiple rows
         *
         * @returns         {Boolean}
         */
        hasMultiselection: function() {
            return this.$el.hasClass('multiselect');
        },

        /**
         * Return all filter keys that are significant when applying the selection
         *
         * @returns         {Array}
         */
        getMultiselectionKeys: function() {
            var data = this.$el.data('icinga-multiselect-data');
            return (data && data.split(',')) || [];
        },

        /**
         * Return the main target URL that is used when multi selecting rows
         *
         * This URL may differ from the url that is used when applying single rows
         *
         * @returns         {String}
         */
        getMultiselectionUrl: function() {
            return this.$el.data('icinga-multiselect-url');
        },

        /**
         * Check whether the given url is
         *
         * @param {String}  url
         */
        hasMultiselectionUrl: function(url) {
            var urls = this.$el.data('icinga-multiselect-url').split(' ');

            var related = this.$el.data('icinga-multiselect-controllers');
            if (related && related.length) {
                urls = urls.concat(this.$el.data('icinga-multiselect-controllers').split(' '));
            }

            var hasSelection = false;
            $.each(urls, function (i, object) {
                if (url.indexOf(object) === 0) {
                    hasSelection = true;
                }
            });
            return hasSelection;
        },

        /**
         * Read all filter data from the given row
         *
         * @param   row     {jQuery}    The row element
         *
         * @returns         {Object}    An object containing all filter data in this row as key-value pairs
         */
        getRowData: function(row) {
            var params = this.icinga.utils.parseUrl(row.attr('href')).params;
            var tuple = {};
            var keys = this.getMultiselectionKeys();
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                if (params[key]) {
                    tuple[key] = params[key];
                }
            }
            return tuple;
        },

        /**
         * Deselect all selected rows
         */
        clear: function() {
            this.selections().removeClass('active');
        },

        /**
         * Add all rows that match the given filter to the selection
         *
         * @param filter {jQuery|Object}    Either an object containing filter variables or the actual row to select
         */
        select: function(filter) {
            if (filter instanceof jQuery) {
                filter.addClass('active');
                return;
            }
            var _this = this;
            this.rowActions()
                .filter(
                    function (i, el) {
                        var params = _this.getRowData($(el));
                        if (_this.icinga.utils.objectKeys(params).length !== _this.icinga.utils.objectKeys(filter).length) {
                            return false;
                        }
                        var equal = true;
                        $.each(params, function(key, value) {
                            if (filter[key] !== value) {
                                equal = false;
                            }
                        });
                        return equal;
                    }
                )
                .closest('tr')
                .addClass('active');
        },

        /**
         * Toggle the selection of the row between on and off
         *
         * @param   row     {jQuery}    The row to toggle
         */
        toggle: function(row) {
            row.toggleClass('active');
        },

        /**
         * Add a new selection range to the closest table, using the selected row as
         * range target.
         *
         * @param   row     {jQuery}    The target of the selected range.
         *
         * @returns         {boolean}   If the selection was changed.
         */
        range: function(row) {
            var from, to;
            var selected = row.first().get(0);
            this.rows().each(function(i, el) {
                if ($(el).hasClass('active') || el === selected) {
                    if (!from) {
                        from = el;
                    }
                    to = el;
                }
            });
            var inRange = false;
            this.rows().each(function(i, el) {
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
         * Select rows that target the given url
         *
         * @param   url     {String}    The target url
         */
        selectUrl: function(url) {
            var formerHref = this.$el.closest('.container').data('icinga-actiontable-former-href')

            var $row = this.rows().filter('[href="' + url + '"]');

            if ($row.length) {
               this.clear();
               $row.addClass('active');
            } else {
                if (this.col !== 'col2') {
                    // rows sometimes need to be displayed as active when related actions
                    // like command actions are being opened. Do not do this for col2, as it
                    // would always select the opened URL itself.
                    var $row = this.rows().filter('[href$="' + icinga.utils.parseUrl(url).query + '"]');
                    if ($row.length) {
                        this.clear();
                        $row.addClass('active');
                    } else {
                        var $row = this.rows().filter('[href$="' + formerHref + '"]');
                        if ($row.length) {
                            this.clear();
                            $row.addClass('active');
                        } else {
                            var tbl = this.$el;
                            if (ActionTable.prototype.tables(
                                tbl.closest('.dashboard').find('.container')).not(tbl).find('tr.active').length
                            ) {
                                this.clear();
                            }
                        }
                    }
                }
            }
        },

        /**
         * Convert all currently selected rows into an url query string
         *
         * @returns         {String}    The filter string
         */
        toQuery: function() {
            var _this = this;
            var selections = this.selections();
            var queries = [];
            var utils = this.icinga.utils;
            if (selections.length === 1) {
                return $(selections[0]).attr('href');
            } else if (selections.length > 1 && _this.hasMultiselection()) {
                selections.each(function (i, el) {
                    var parts = [];
                    $.each(_this.getRowData($(el)), function(key, value) {
                        parts.push(utils.fixedEncodeURIComponent(key) + '=' + utils.fixedEncodeURIComponent(value));
                    });
                    queries.push('(' + parts.join('&') + ')');
                });
                return _this.getMultiselectionUrl() + '?(' + queries.join('|') + ')';
            } else {
                return '';
            }
        },

        /**
         * Refresh the displayed active columns using the current page location
         */
        refresh: function() {
            var hash = icinga.history.getCol2State().replace(/^#!/, '');
            if (this.hasMultiselection()) {
                var query = parseSelectionQuery(hash);
                if (query.length > 1 && this.hasMultiselectionUrl(this.icinga.utils.parseUrl(hash).path)) {
                    this.clear();
                    // select all rows with matching filters
                    var _this = this;
                    $.each(query, function(i, selection) {
                        _this.select(selection);
                    });
                }
                if (query.length > 1) {
                    return;
                }
            }
            this.selectUrl(hash);
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    var ActionTable = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        /**
         * If currently loading
         *
         * @var Boolean
         */
        this.loading = false;

        this.on('rendered', this.onRendered, this);
        this.on('beforerender', this.beforeRender, this);
        this.on('click', 'table.action tr[href], table.table-row-selectable tr[href]', this.onRowClicked, this);
    };
    ActionTable.prototype = new Icinga.EventListener();

    /**
     * Return all active tables in this table, or in the context as jQuery selector
     *
     * @param   context   {HTMLElement}
     * @returns           {jQuery}
     */
    ActionTable.prototype.tables = function(context) {
        if (context) {
            return $(context).find('table.action, table.table-row-selectable');
        }
        return $('table.action, table.table-row-selectable');
    };

    /**
     * Handle clicks on table rows and update selection and history
     */
    ActionTable.prototype.onRowClicked = function (event) {
        var _this = event.data.self;
        var $target = $(event.target);
        var $tr = $target.closest('tr');
        var table = new Selection($tr.closest('table.action, table.table-row-selectable')[0], _this.icinga);

        // some rows may contain form actions that trigger a different action, pass those through
        if (!$target.hasClass('rowaction') && $target.closest('form').length &&
            ($target.closest('a').length ||                                         // allow regular link clinks
             $target.closest('button').length ||                                    // allow submitting forms
             $target.closest('input').length || $target.closest('label').length)) { // allow selecting form elements
            return;
        }

        event.stopPropagation();
        event.preventDefault();

        // update selection
        if (table.hasMultiselection()) {
            if (event.ctrlKey || event.metaKey) {
                // add to selection
                table.toggle($tr);
            } else if (event.shiftKey) {
                // range selection
                table.range($tr);
            } else {
                // single selection
                table.clear();
                table.select($tr);
            }
        } else {
            table.clear();
            table.select($tr);
        }

        // update history
        var state = icinga.history.getCol1State();
        var count = table.selections().length;
        if (count > 0) {
            var query = table.toQuery();
            _this.icinga.loader.loadUrl(query, _this.icinga.events.getLinkTargetFor($tr));
            state += '#!' + query;
        } else {
            if (_this.icinga.events.getLinkTargetFor($tr).attr('id') === 'col2') {
                _this.icinga.ui.layout1col();
            }
        }
        _this.icinga.history.pushUrl(state);

        // redraw all table selections
        _this.tables().each(function () {
            new Selection(this, _this.icinga).refresh();
        });

        // update selection info
        $('.selection-info-count').text(table.selections().size());
        return false;
    };

    /**
     * Render the selection and prepare selection rows
     */
    ActionTable.prototype.onRendered = function(evt) {
        var container = evt.target;
        var _this = evt.data.self;

        // initialize all rows with the correct row action
        $('table.action tr, table.table-row-selectable tr', container).each(function(idx, el) {

            // decide which row action to use: links declared with the class rowaction take
            // the highest precedence before hrefs defined in the tr itself and regular links
            var $a = $('a[href].rowaction', el).first();
            if ($a.length) {
                // TODO: Find out whether we leak memory on IE with this:
                $(el).attr('href', $a.attr('href'));
                return;
            }
            if ($(el).attr('href') && $(el).attr('href').length) {
                return;
            }
            $a = $('a[href]', el).first();
            if ($a.length) {
                $(el).attr('href', $a.attr('href'));
            }
        });

        // IE will not ignore user-select unless we cancel selectstart
        $('table.action.multiselect tr, table.table-row-selectable.multiselect tr', container).each(function(idx, el) {
            $(el).on('selectstart', false);
        });

        // draw all active selections that have disappeared on reload
        _this.tables().each(function(i, el) {
            new Selection(el, _this.icinga).refresh();
        });

        // update displayed selection counter
        var table = new Selection(_this.tables(container).first());
        $(container).find('.selection-info-count').text(table.selections().size());
    };

    ActionTable.prototype.beforeRender = function(evt) {
        var container = evt.target;
        var _this = evt.data.self;

        var active = _this.tables().find('tr.active');
        if (active.length) {
            $(container).data('icinga-actiontable-former-href', active.attr('href'));
        }
    };

    ActionTable.prototype.clearAll = function () {
        var _this = this;
        this.tables().each(function () {
            new Selection(this, _this.icinga).clear();
        });
        $('.selection-info-count').text('0');
    };

    Icinga.Behaviors.ActionTable = ActionTable;

}) (Icinga, jQuery);
