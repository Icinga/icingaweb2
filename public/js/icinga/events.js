/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Events
 *
 * Event handlers
 */
(function (Icinga, $) {

    'use strict';

    Icinga.Events = function (icinga) {
        this.icinga = icinga;

        this.searchValue = '';
    };

    Icinga.Events.prototype = {

        /**
         * Icinga will call our initialize() function once it's ready
         */
        initialize: function () {
            this.applyGlobalDefaults();
            $('#layout').trigger('rendered');
            //$('.container').trigger('rendered');
            $('.container').each(function(idx, el) {
                icinga.ui.initializeControls($(el));
            });
        },

        // TODO: What's this?
        applyHandlers: function (evt) {
            var el = $(evt.target), self = evt.data.self;
            var icinga = self.icinga;

            $('.dashboard > div', el).each(function(idx, el) {
                var url = $(el).data('icingaUrl');
                if (typeof url === 'undefined') return;
                icinga.loader.loadUrl(url, $(el)).autorefresh = true;
            });

            // Set first links href in a action table tr as row href:
            $('table.action tr', el).each(function(idx, el) {
                var $a = $('a[href]', el).first();
                if ($a.length) {
                    // TODO: Find out whether we leak memory on IE with this:
                    $(el).attr('href', $a.attr('href'));
                }
            });

            $('td.state span.timesince').attr('title', null);

            var moduleName = el.data('icingaModule');
            if (moduleName) {
                if (icinga.hasModule(moduleName)) {
                    var module = icinga.module(moduleName);
                    // NOT YET, the applyOnloadDings: module.applyEventHandlers(mod);
                }
            }

            $('.icinga-module', el).each(function(idx, mod) {
                var $mod = $(mod);
                moduleName = $mod.data('icingaModule');
                if (icinga.hasModule(moduleName)) {
                    var module = icinga.module(moduleName);
                    // NOT YET, the applyOnloadDings: module.applyEventHandlers(mod);
                }
            });

            var searchField = $('#menu input.search', el);
            // Remember initial search field value if any
            if (searchField.length && searchField.val().length) {
                self.searchValue = searchField.val();
            }

            if (icinga.ui.isOneColLayout()) {
                icinga.ui.disableCloseButtons();
            } else {
                icinga.ui.enableCloseButtons();
            }
        },

        /**
         * Global default event handlers
         */
        applyGlobalDefaults: function () {
            $.each(self.icinga.behaviors, function (name, behavior) {
                behavior.bind($(document));
            });

            // Apply element-specific behavior whenever the layout is rendered
            $(document).on('rendered', { self: this }, this.applyHandlers);

            // We catch resize events
            $(window).on('resize', { self: this.icinga.ui }, this.icinga.ui.onWindowResize);

            // Trigger 'rendered' event also on page loads
            $(window).on('load', { self: this }, this.onLoad);

            // Destroy Icinga, clean up and interrupt pending requests on unload
            $( window ).on('unload', { self: this }, this.onUnload);
            $( window ).on('beforeunload', { self: this }, this.onUnload);

            // We catch scroll events in our containers
            $('.container').on('scroll', { self: this }, this.icinga.events.onContainerScroll);

            // We want to catch each link click
            $(document).on('click', 'a', { self: this }, this.linkClicked);
            $(document).on('click', 'tr[href]', { self: this }, this.linkClicked);

            // Select a table row
            $(document).on('click', 'table.multiselect tr[href]', { self: this }, this.rowSelected);

            // We catch all form submit events
            $(document).on('submit', 'form', { self: this }, this.submitForm);

            // We support an 'autosubmit' class on dropdown form elements
            $(document).on('change', 'form select.autosubmit', { self: this }, this.autoSubmitForm);
            $(document).on('change', 'form input.autosubmit', { self: this }, this.autoSubmitForm);

            // Automatically check a radio button once a specific input is focused
            $(document).on('focus', 'form select[data-related-radiobtn]', { self: this }, this.autoCheckRadioButton);
            $(document).on('focus', 'form input[data-related-radiobtn]', { self: this }, this.autoCheckRadioButton);

            $(document).on('keyup', '#menu input.search', {self: this}, this.autoSubmitSearch);

            $(document).on('click', '.tree .handle', { self: this }, this.treeNodeToggle);

            // TBD: a global autocompletion handler
            // $(document).on('keyup', 'form.auto input', this.formChangeDelayed);
            // $(document).on('change', 'form.auto input', this.formChanged);
            // $(document).on('change', 'form.auto select', this.submitForm);
        },

        treeNodeToggle: function () {
            var $parent = $(this).closest('li');
            if ($parent.hasClass('collapsed')) {
                $('li', $parent).addClass('collapsed');
                $parent.removeClass('collapsed');
            } else {
                $parent.addClass('collapsed');
            }
        },

        onLoad: function (event) {
            //$('.container').trigger('rendered');
        },

        onUnload: function (event) {
            var icinga = event.data.self.icinga;
            icinga.logger.info('Unloading Icinga');
            icinga.destroy();
        },

        /**
         * A scroll event happened in one of our containers
         */
        onContainerScroll: function (event) {
            // Ugly. And PLEASE, not so often
            icinga.ui.fixControls();
        },

        autoCheckRadioButton: function (event) {
            var $input = $(event.currentTarget);
            var $radio = $('#' + $input.attr('data-related-radiobtn'));
            if ($radio.length) {
                $radio.prop('checked', true);
            }
            return true;
        },

        autoSubmitSearch: function(event) {
            var self = event.data.self;
            if ($('#menu input.search').val() === self.searchValue) {
                return;
            }
            self.searchValue = $('#menu input.search').val();
            return self.autoSubmitForm(event);
        },

        autoSubmitForm: function (event) {
            return event.data.self.submitForm(event, true);
        },

        /**
         *
         */
        submitForm: function (event, autosubmit) {
            //return false;
            var self   = event.data.self;
            var icinga = self.icinga;
            // .closest is not required unless subelements to trigger this
            var $form = $(event.currentTarget).closest('form');
            var regex = new RegExp('&amp;', 'g');
            var url = $form.attr('action').replace(regex, '&'); // WHY??
            var method = $form.attr('method');
            var $button = $('input[type=submit]:focus', $form).add('button[type=submit]:focus', $form);
            var $target;
            var data;

            if ($button.length === 0) {
                var $el;

                if (typeof event.originalEvent !== 'undefined'
                    && typeof event.originalEvent.explicitOriginalTarget === 'object') { // Firefox
                    $el = $(event.originalEvent.explicitOriginalTarget);
                    icinga.logger.debug('events/submitForm: Button is event.originalEvent.explicitOriginalTarget');
                } else {
                    $el = $(event.currentTarget);
                    icinga.logger.debug('events/submitForm: Button is event.currentTarget');
                }

                if ($el && ($el.is('input[type=submit]') || $el.is('button[type=submit]'))) {
                    $button = $el;
                } else {
                    icinga.logger.debug(
                        'events/submitForm: Can not determine submit button, using the first one in form'
                    );
                }
            }

            if (typeof method === 'undefined') {
                method = 'POST';
            } else {
                method = method.toUpperCase();
            }

            if ($button.length === 0) {
                $button = $('input[type=submit]', $form).add('button[type=submit]', $form).first();
            }

            event.stopPropagation();
            event.preventDefault();

            if ($button.length) {
                // Activate spinner
                if ($button.hasClass('spinner')) {
                    $button.addClass('active');
                }

                $target = self.getLinkTargetFor($button);
            } else {
                $target = self.getLinkTargetFor($form);
            }

            if (! url) {
                // Use the URL of the target container if the form's action is not set
                url = $target.closest('.container').data('icinga-url');
            }

            icinga.logger.debug('Submitting form: ' + method + ' ' + url, method);

            if (method === 'GET') {
                var dataObj = $form.serializeObject();

                if (typeof autosubmit === 'undefined' || ! autosubmit) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        dataObj[$button.attr('name')] = $button.attr('value');
                    }
                }

                url = icinga.utils.addUrlParams(url, dataObj);
            } else {
                data = $form.serializeArray();

                if (typeof autosubmit === 'undefined' || ! autosubmit) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        data.push({
                            name: $button.attr('name'),
                            value: $button.attr('value')
                        });
                    }
                }
            }

            icinga.loader.loadUrl(url, $target, data, method);

            return false;
        },

        handleExternalTarget: function($node) {
            var linkTarget = $node.attr('target');

            // TODO: Let remote links pass through. Right now they only work
            //       combined with target="_blank" or target="_self"
            // window.open is used as return true; didn't work reliable
            if (linkTarget === '_blank' || linkTarget === '_self') {
                window.open($node.attr('href'), linkTarget);
                return true;
            }
            return false;
        },

        /**
         * Handle table selection.
         */
        rowSelected: function(event) {
            var self     = event.data.self;
            var icinga   = self.icinga;
            var $tr      = $(this);
            var $table   = $tr.closest('table.multiselect');
            var data     = self.icinga.ui.getSelectionKeys($table);
            var url      = $table.data('icinga-multiselect-url');

            event.stopPropagation();
            event.preventDefault();

            if (!data) {
                icinga.logger.error('multiselect table has no data-icinga-multiselect-data');
                return;
            }
            if (!url) {
                icinga.logger.error('multiselect table has no data-icinga-multiselect-url');
                return;
            }

            // update selection
            if (event.ctrlKey || event.metaKey) {
                icinga.ui.toogleTableRowSelection($tr);
                // multi selection
            } else if (event.shiftKey) {
                // range selection
                icinga.ui.addTableRowRangeSelection($tr);
            } else {
                // single selection
                icinga.ui.setTableRowSelection($tr);
            }
            // focus only the current table.
            icinga.ui.focusTable($table[0]);

            var $target = self.getLinkTargetFor($tr);

            var $trs = $table.find('tr[href].active');
            if ($trs.length > 1) {
                var selectionData = icinga.ui.getSelectionSetData($trs, data);
                var query = icinga.ui.selectionDataToQuery(selectionData);
                icinga.loader.loadUrl(url + '?' + query, $target);
                icinga.ui.storeSelectionData(selectionData);
                icinga.ui.provideSelectionCount();
            } else if ($trs.length === 1) {
                // display a single row
                $tr = $trs.first();
                icinga.loader.loadUrl($tr.attr('href'), $target);
                icinga.ui.storeSelectionData($tr.attr('href'));
                icinga.ui.provideSelectionCount();
            } else {
                // display nothing
                if ($target.attr('id') === 'col2') {
                    icinga.ui.layout1col();
                }
                icinga.ui.storeSelectionData(null);
                icinga.ui.provideSelectionCount();
            }

            return false;
        },

        /**
         * Handle anchor, i.e. focus the element which is referenced by the anchor
         *
         * @param {string} query jQuery selector
         */
        handleAnchor: function(query) {
            var $element = $(query);
            if ($element.length > 0) {
                if (typeof $element.attr('tabindex') === 'undefined') {
                    $element.attr('tabindex', -1);
                }
                $element.focus();
            }
        },

        /**
         * Someone clicked a link or tr[href]
         */
        linkClicked: function (event) {
            var self   = event.data.self;
            var icinga = self.icinga;
            var $a = $(this);
            var $eventTarget = $(event.target);
            var href = $a.attr('href');
            var linkTarget = $a.attr('target');
            var $target;
            var formerUrl;
            var remote = /^(?:[a-z]+:)\/\//;
            if (href.match(/^(mailto|javascript):/)) {
                return true;
            }

            // Special checks for link clicks in multiselect rows
            if (! $a.is('tr[href]') && $a.closest('tr[href]').length > 0 && $a.closest('table.multiselect').length > 0) {

                // Forward clicks to ANY link with special key pressed to rowSelected
                if (event.ctrlKey || event.metaKey || event.shiftKey)
                {
                    return self.rowSelected.call($a.closest('tr[href]'), event);
                }

                // Forward inner links matching the row URL to rowSelected
                if ($a.attr('href') === $a.closest('tr[href]').attr('href'))
                {
                    return self.rowSelected.call($a.closest('tr[href]'), event);
                }
            }

            // Let remote links pass through
            if  (href.match(remote)) {
                return true;
            }

            // window.open is used as return true; didn't work reliable
            if (linkTarget === '_blank' || linkTarget === '_self') {
                window.open(href, linkTarget);
                return false;
            }

            if (! $eventTarget.is($a)) {
                if ($eventTarget.is('input') || $eventTarget.is('button')) {
                    // Ignore form elements in action rows
                    return;
                } else {
                    var $button = $('input[type=submit]:focus').add('button[type=submit]:focus');
                    if ($button.length > 0 && $.contains($button[0], $eventTarget[0])) {
                        // Ignore any descendant of form elements
                        return;
                    }
                }
            }

            // ignore multiselect table row clicks
            if ($a.is('tr') && $a.closest('table.multiselect').length > 0) {
                return;
            }

            // Handle all other links as XHR requests
            event.stopPropagation();
            event.preventDefault();

            // This is an anchor only
            if (href.substr(0, 1) === '#' && href.length > 1
                && href.substr(1, 1) !== '!') {
                self.handleAnchor(href);
                return;
            }

            // activate spinner indicator
            if ($a.hasClass('spinner')) {
                $a.addClass('active');
            }

            // If link has hash tag...
            if (href.match(/#/)) {
                if (href === '#') {
                    if ($a.hasClass('close-toggle')) {
                        if (! icinga.ui.isOneColLayout()) {
                            var $cont = $a.closest('.container').first();
                            if ($cont.attr('id') === 'col1') {
                                icinga.ui.moveToLeft();
                                icinga.ui.layout1col();
                            } else {
                                icinga.ui.layout1col();
                            }
                            $('table tr[href].active').removeClass('active');
                            icinga.ui.storeSelectionData(null);
                            icinga.ui.loadSelectionData();
                            icinga.history.pushCurrentState();
                        }
                    }
                    return false;
                }
                $target = self.getLinkTargetFor($a);

                formerUrl = $target.data('icingaUrl');
                if (typeof formerUrl !== 'undefined' && formerUrl.split(/#/)[0] === href.split(/#/)[0]) {
                    icinga.ui.scrollContainerToAnchor($target, href.split(/#/)[1]);
                    $target.data('icingaUrl', href);
                    if (formerUrl !== href) {
                        icinga.history.pushCurrentState();
                    }
                    return false;
                }
            } else {
                $target = self.getLinkTargetFor($a);
            }

            // Load link URL
            icinga.loader.loadUrl(href, $target);

            if ($a.closest('#menu').length > 0) {
                // Menu links should remove all but the first layout column
                icinga.ui.layout1col();
            }

            return false;
        },

        /**
         * Detect the link/form target for a given element (link, form, whatever)
         */
        getLinkTargetFor: function($el)
        {
            var targetId;

            // If everything else fails, our target is the first column...
            var $target = $('#col1');

            // ...but usually we will use our own container...
            var $container = $el.closest('.container');
            if ($container.length) {
                $target = $container;
            }

            // You can of course override the default behaviour:
            if ($el.closest('[data-base-target]').length) {
                targetId = $el.closest('[data-base-target]').data('baseTarget');

                // Simulate _next to prepare migration to dynamic column layout
                // YES, there are duplicate lines right now.
                if (targetId === '_next') {
                    if (this.icinga.ui.hasOnlyOneColumn()) {
                        targetId = 'col1';
                        $target = $('#' + targetId);
                    } else {
                        if ($el.closest('#col2').length) {
                            this.icinga.ui.moveToLeft();
                        }
                        targetId = 'col2';
                        $target = $('#' + targetId);
                    }
                } else if (targetId === '_self') {
                    $target = $el.closest('.container');
                    targetId = $target.attr('id');
                } else if (targetId === '_main') {
                    targetId = 'col1';
                    $target = $('#' + targetId);
                    self.icinga.ui.layout1col();
                } else {
                    $target = $('#' + targetId);
                }

            }

            // Hardcoded layout switch unless columns are dynamic
            if ($target.attr('id') === 'col2') {
                this.icinga.ui.layout2col();
            }

            return $target;
        },

        unbindGlobalHandlers: function () {
            $.each(self.icinga.behaviors, function (name, behavior) {
                behavior.unbind($(document));
            });
            $(window).off('resize', this.onWindowResize);
            $(window).off('load', this.onLoad);
            $(window).off('unload', this.onUnload);
            $(window).off('beforeunload', this.onUnload);
            $(document).off('scroll', '.container', this.onContainerScroll);
            $(document).off('click', 'a', this.linkClicked);
            $(document).off('click', 'table.action tr[href]', this.rowSelected);
            $(document).off('click', 'table.action tr a', this.rowSelected);
            $(document).off('submit', 'form', this.submitForm);
            $(document).off('change', 'form select.autosubmit', this.submitForm);
            $(document).off('change', 'form input.autosubmit', this.submitForm);
            $(document).off('focus', 'form select[data-related-radiobtn]', this.autoCheckRadioButton);
            $(document).off('focus', 'form input[data-related-radiobtn]', this.autoCheckRadioButton);
        },

        destroy: function() {
            // This is gonna be hard, clean up the mess
            this.unbindGlobalHandlers();
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
