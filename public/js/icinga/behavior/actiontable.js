/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            return this.$el.find('tr a.rowaction');
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
         * Return the target URL that is used when multi selecting rows
         *
         * This URL may differ from the url that is used when applying single rows
         *
         * @returns         {String}
         */
        getMultiselectionUrl: function() {
            return this.$el.data('icinga-multiselect-url');
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
            var self = this;
            this.rowActions()
                .filter(
                    function (i, el) {
                        var params = self.getRowData($(el));
                        if (self.icinga.utils.objectKeys(params).length !== self.icinga.utils.objectKeys(filter).length) {
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
            this.rows().filter('[href="' + url + '"]').addClass('active');
        },

        /**
         * Convert all currently selected rows into an url query string
         *
         * @returns         {String}    The filter string
         */
        toQuery: function() {
            var self = this;
            var selections = this.selections();
            var queries = [];
            if (selections.length === 1) {
                return $(selections[0]).attr('href');
            } else if (selections.length > 1 && self.hasMultiselection()) {
                selections.each(function (i, el) {
                    var parts = [];
                    $.each(self.getRowData($(el)), function(key, value) {
                        parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    });
                    queries.push('(' + parts.join('&') + ')');
                });
                return self.getMultiselectionUrl() + '?(' + queries.join('|') + ')';
            } else {
                return '';
            }
        },

        /**
         * Refresh the displayed active columns using the current page location
         */
        refresh: function() {
            this.clear();
            var hash = this.icinga.utils.parseUrl(window.location.href).hash;
            if (this.hasMultiselection()) {
                var query = parseSelectionQuery(hash);
                if (query.length > 1 && this.getMultiselectionUrl() === this.icinga.utils.parseUrl(hash.substr(1)).path) {
                    // select all rows with matching filters
                    var self = this;
                    $.each(query, function(i, selection) {
                        self.select(selection);
                    });
                }
                if (query.length > 1) {
                    return;
                }
            }
            this.selectUrl(hash.substr(1));
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
        this.on('click', 'table.action tr[href]', this.onRowClicked, this);
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
            return $(context).find('table.action');
        }
        return $('table.action');
    };

    /**
     * Handle clicks on table rows and update selection and history
     */
    ActionTable.prototype.onRowClicked = function (event) {
        var self = event.data.self;
        var $target = $(event.target);
        var $tr = $target.closest('tr');
        var table = new Selection($tr.closest('table.action')[0], self.icinga);

        // some rows may contain form actions that trigger a different action, pass those through
        if (!$target.hasClass('rowaction') && $target.closest('form').length &&
            ($target.closest('a').length || $target.closest('button').length)) {
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
        var url = self.icinga.utils.parseUrl(window.location.href.split('#')[0]);
        var count = table.selections().length;
        var state = url.path + url.query;
        if (count > 0) {
            var query = table.toQuery();
            self.icinga.loader.loadUrl(query, self.icinga.events.getLinkTargetFor($tr));
            state +=  '#!' + query;
        } else {
            if (self.icinga.events.getLinkTargetFor($tr).attr('id') === 'col2') {
                self.icinga.ui.layout1col();
            }
        }
        self.icinga.history.pushUrl(state);
        
        // redraw all table selections
        self.tables().each(function () {
            new Selection(this, self.icinga).refresh();
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
        var self = evt.data.self;

        // initialize all rows with the correct link
        $('table.action tr', container).each(function(idx, el) {
            // IE will not ignore user-select unless we cancel selectstart
            $(el).on('selectstart', false);

            var $a = $('a[href].rowaction', el).first();
            if ($a.length) {
                // TODO: Find out whether we leak memory on IE with this:
                $(el).attr('href', $a.attr('href'));
                return;
            }
            $a = $('a[href]', el).first();
            if ($a.length) {
                $(el).attr('href', $a.attr('href'));
            }
        });

        // draw all active selections that have disappeared on reload
        self.tables().each(function(i, el) {
            new Selection(el, self.icinga).refresh();
        });

        // update displayed selection counter
        var table = new Selection(self.tables(container).first());
        $(container).find('.selection-info-count').text(table.selections().size());
    };

    ActionTable.prototype.clearAll = function () {
        var self = this;
        this.tables().each(function () {
            new Selection(this, self.icinga).clear();
        });
    };

    Icinga.Behaviors.ActionTable = ActionTable;

}) (Icinga, jQuery);
