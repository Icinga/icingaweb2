/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Selection
 * 
 * A multi selection that distincts between the rows using the row action URL filter
 */
(function(Icinga, $) {

    "use strict";

    var stripBrackets = function (str) {
        return str.replace(/^[^\(]*\(/, '').replace(/\)[^\)]*$/, '');
    };

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

    var toQueryPart = function(id) {
        var queries = [];
        $.each(id, function(key, value) {
            queries.push(key + '=' + encodeURIComponent(value));
        });
        return queries.join('&');
    };

    var Table = function(table, icinga) {
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

    Table.prototype = {
        rows: function() {
            return this.$el.find('tr');
        },

        rowActions: function() {
            return this.$el.find('tr a.rowaction');
        },

        selections: function() {
            return this.$el.find('tr.active');
        },

        hasMultiselection: function() {
            return this.$el.hasClass('multiselect');
        },

        getMultiselectionKeys: function() {
            var data = this.$el.data('icinga-multiselect-data');
            return (data && data.split(',')) || [];
        },

        getMultiselectionUrl: function() {
            return this.$el.data('icinga-multiselect-url');
        },

        /**
         * @param   row     {jQuery}    The row
         *
         * @returns         {Object}    An object containing all selection data in
         *                              this row as key-value pairs
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

        clear: function() {
            this.selections().removeClass('active');
        },

        select: function(filter) {
            if (filter instanceof jQuery) {
                filter.addClass('active');
                return;
            }
            var self = this;
            var url = this.getMultiselectionUrl();
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

        toggle: function(filter) {
            if (filter instanceof jQuery) {
                filter.toggleClass('active');
                return;
            }
            this.icinga.logger.error('toggling by filter not implemented');
        },

        /**
         * Add a new selection range to the closest table, using the selected row as
         * range target.
         *
         * @param   row {jQuery}    The target of the selected range.
         *
         * @returns     {boolean}   If the selection was changed.
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

        selectUrl: function(url) {
            this.rows().filter('[href="' + url + '"]').addClass('active');
        },

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

    var Selection = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        
       /**
        * The hash that is currently being loaded
        *
        * @var String
        */
        this.loadingHash = null;
        
        /**
         * If currently loading
         *
         * @var Boolean
         */
        this.loading = false;

        this.on('rendered', this.onRendered, this);
        this.on('click', 'table.action tr[href]', this.onRowClicked, this);
    };
    Selection.prototype = new Icinga.EventListener();

    Selection.prototype.tables = function(context) {
        if (context) {
            return $(context).find('table.action');
        }
        return $('table.action');
    };

    Selection.prototype.onRowClicked = function(event) {
        var self = event.data.self;
        var $tr = $(event.target).closest('tr');
        var table = new Table($tr.closest('table.action')[0], self.icinga);

        // allow form actions in table rows to pass through
        if ($(event.target).closest('form').length) {
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
        
        // re draw all table selections
        self.tables().each(function () {
            new Table(this, self.icinga).refresh();
        });

        // update selection info
        $('.selection-info-count').text(table.selections().size());
        return false;
    }

    Selection.prototype.onRendered = function(evt) {
        var container = evt.target;
        var self = evt.data.self;

        // draw all selections
        self.tables().each(function(i, el) {
            new Table(el, self.icinga).refresh();
        });

        // update displayed selection count
        var table = new Table(self.tables(container).first());
        $(container).find('.selection-info-count').text(table.selections().size());
    };

    Icinga.Behaviors.Selection = Selection;

}) (Icinga, jQuery);
