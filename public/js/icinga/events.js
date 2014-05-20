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

        keyboard: {
            ctrlKey:    false,
            altKey:     false,
            shiftKey:   false
        },

        /**
         * Icinga will call our initialize() function once it's ready
         */
        initialize: function () {
            this.applyGlobalDefaults();
            this.applyHandlers($('#layout'));
            this.icinga.ui.prepareContainers();
            this.icinga.ui.prepareMultiselectTables($(document));
        },

        // TODO: What's this?
        applyHandlers: function (el) {

            var icinga = this.icinga;

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

            var moduleName;
            if (moduleName = el.data('icingaModule')) {
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

            $('input.autofocus', el).focus();

            $('img.inlinepie', el).each(function() {
                icinga.ui.initializeSparklines($(this));
            });

            // replace all sparklines
            $('span.sparkline', el).sparkline('html', { enableTagOptions: true });

            var searchField = $('#menu input.search', el);
            // Remember initial search field value if any
            if (searchField.length && searchField.val().length) {
                this.searchValue = searchField.val();
            }
        },

        /**
         * Global default event handlers
         */
        applyGlobalDefaults: function () {
            // We catch resize events
            $(window).on('resize', { self: this.icinga.ui }, this.icinga.ui.onWindowResize);

            // Trigger 'rendered' event also on page loads
            $(window).on('load', { self: this }, this.onLoad);

            // Destroy Icinga, clean up and interrupt pending requests on unload
            $( window ).on('unload', { self: this }, this.onUnload);
            $( window ).on('beforeunload', { self: this }, this.onUnload);

            // We catch scroll events in our containers
            $('.container').on('scroll', this.icinga.events.onContainerScroll);

            // We want to catch each link click
            $(document).on('click', 'a', { self: this }, this.linkClicked);
            $(document).on('click', 'tr[href]', { self: this }, this.linkClicked);

            // Select a table row
            $(document).on('click', 'table.multiselect tr[href]', { self: this }, this.rowSelected);

            $(document).on('click', 'button', { self: this }, this.submitForm);

            // We catch all form submit events
            $(document).on('submit', 'form', { self: this }, this.submitForm);

            // We support an 'autosubmit' class on dropdown form elements
            $(document).on('change', 'form select.autosubmit', { self: this }, this.autoSubmitForm);

            $(document).on('keyup', '#menu input.search', {self: this}, this.autoSubmitSearch);

            $(document).on('mouseenter', '.historycolorgrid td', this.historycolorgridHover);
            $(document).on('mouseleave', '.historycolorgrid td', this.historycolorgidUnhover);
            $(document).on('mouseenter', 'li.dropdown', this.dropdownHover);
            $(document).on('mouseleave', 'li.dropdown', this.dropdownLeave);

            $(document).on('mouseenter', '#menu > ul > li', this.menuTitleHovered);
            $(document).on('mouseleave', '#sidebar', this.leaveSidebar);
            $(document).on('click', '.tree .handle', { self: this }, this.treeNodeToggle);

            // Toggle all triStateButtons
            $(document).on('click', 'div.tristate .tristate-dummy', { self: this }, this.clickTriState);

            // TBD: a global autocompletion handler
            // $(document).on('keyup', 'form.auto input', this.formChangeDelayed);
            // $(document).on('change', 'form.auto input', this.formChanged);
            // $(document).on('change', 'form.auto select', this.submitForm);
        },

        menuTitleHovered: function () {
            var $li = $(this),
                delay = 800;

            if ($li.hasClass('active')) {
                $li.siblings().removeClass('hover');
                return;
            }
            if ($li.children('ul').children('li').length === 0) {
                return;
            }

            if ($('#layout').hasClass('hoveredmenu')) {
                delay = 0;
            }

            setTimeout(function () {
                if (! $li.is('li:hover')) {
                    return;
                }
                if ($li.hasClass('active')) {
                    return;
                }

                $li.siblings().each(function () {
                    var $sibling = $(this);
                    if ($sibling.is('li:hover')) {
                        return;
                    }
                    if ($sibling.hasClass('hover')) {
                        $sibling.removeClass('hover');
                    }
                });

                $('#layout').addClass('hoveredmenu');
                $li.addClass('hover');
            }, delay);
        },

        leaveSidebar: function () {
            var $sidebar = $(this);
            var $li = $sidebar.find('li.hover');
            if (! $li.length) {
                return;
            }

            setTimeout(function () {
                if ($li.is('li:hover') || $sidebar.is('sidebar:hover') ) {
                    return;
                }
                $li.removeClass('hover');
                $('#layout').removeClass('hoveredmenu');
            }, 500);
        },

        dropdownHover: function () {
            $(this).addClass('hover');
        },

        dropdownLeave: function () {
            var $li = $(this);
            setTimeout(function () {
                // TODO: make this behave well together with keyboard navigation
                if (! $li.is('li:hover') /*&& ! $li.find('a:focus')*/) {
                    $li.removeClass('hover');
                }
            }, 300);
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
            $('.container').trigger('rendered');
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

        historycolorgridHover: function () {
            $(this).addClass('hover');
        },

        historycolorgidUnhover: function() {
            $(this).removeClass('hover');
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

        clickTriState: function (event) {
            var $tristate = $(this);
            var triState  = parseInt($tristate.data('icinga-tristate'), 10);

            // load current values
            var old   = $tristate.data('icinga-old').toString();
            var value = $tristate.parent().find('input:radio:checked').first().prop('checked', false).val();

            // calculate the new value
            if (triState) {
                // 1         => 0
                // 0         => unchanged
                // unchanged => 1
                value = value === '1' ? '0' : (value === '0' ? 'unchanged' : '1');
            } else {
                // 1 => 0
                // 0 => 1
                value = value === '1' ? '0' : '1';
            }

            // update form value
            $tristate.parent().find('input:radio[value="' + value + '"]').prop('checked', true);
            // update dummy

            if (value !== old) {
                $tristate.parent().find('b.tristate-changed').css('visibility', 'visible');
            } else {
                $tristate.parent().find('b.tristate-changed').css('visibility', 'hidden');
            }
            self.icinga.ui.setTriState(value.toString(), $tristate);    
        },

        /**
         *
         */
        submitForm: function (event, autosubmit) {
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

            if (typeof method === 'undefined') {
                method = 'POST';
            } else {
                method = method.toUpperCase();
            }

            if ($button.length === 0) {
                $button = $('input[type=submit]', $form).first();
            }

            event.stopPropagation();
            event.preventDefault();

            icinga.logger.debug('Submitting form: ' + method + ' ' + url, method);

            $target = self.getLinkTargetFor($form);

            if (method === 'GET') {
                url = icinga.utils.addUrlParams(url, $form.serializeObject());
            } else {
                data = $form.serializeArray();

                if (typeof autosubmit === 'undefined' || ! autosubmit) {
                    if ($button.length) {
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
                window.open(href, linkTarget);
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
            var multisel = $table.hasClass('multiselect');
            var url      = $table.data('icinga-multiselect-url');

            // When the selection points to a link, select the closest row
            if ($tr.prop('tagName').toLowerCase() === 'a') {
                $tr = $tr.closest('tr').first();
            }

            event.stopPropagation();
            event.preventDefault();

            if (icinga.events.handleExternalTarget($tr)) {
                // link handled externally
                return false;
            }
            if (multisel && !data) {
                icinga.logger.error('A table with multiselection must define the attribute "data-icinga-multiselect-data"');
                return;
            }
            if (multisel && !url) {
                icinga.logger.error('A table with multiselection must define the attribute "data-icinga-multiselect-url"');
                return;
            }

            // update selection
            if ((event.ctrlKey || event.metaKey) && multisel) {
                icinga.ui.toogleTableRowSelection($tr);
                // multi selection
            } else if (event.shiftKey && multisel) {
                // range selection
                icinga.ui.addTableRowRangeSelection($tr);
            } else {
                // single selection
                icinga.ui.setTableRowSelection($tr);
            }
            // focus only the current table.
            icinga.ui.focusTable($table[0]);

            // update url
            var $target = self.getLinkTargetFor($tr);
            if (multisel) {
                var $trs = $table.find('tr[href].active');
                if ($trs.length > 1) {
                    var queries = [];
                    var selectionData = icinga.ui.getSelectionSetData($trs, data);
                    var query = icinga.ui.selectionDataToQuery(selectionData, data, icinga);
                    icinga.loader.loadUrl(url + '?' + query, $target);
                    icinga.ui.storeSelectionData(selectionData);
                } else if ($trs.length === 1) {
                    // display a single row
                    icinga.loader.loadUrl($tr.attr('href'), $target);
                    icinga.ui.storeSelectionData($tr.attr('href'));
                } else {
                    // display nothing
                    icinga.loader.loadUrl('#');
                    icinga.ui.storeSelectionData(null);
                }
            } else {
                icinga.loader.loadUrl($tr.attr('href'), $target);
            }
            return false;
        },


        /**
         * Someone clicked a link or tr[href]
         */
        linkClicked: function (event) {
            var self   = event.data.self;
            var icinga = self.icinga;
            var $a = $(this);
            var href = $a.attr('href');
            var linkTarget = $a.attr('target');
            var $li;
            var $target;
            var isMenuLink = $a.closest('#menu').length > 0;
            var formerUrl;
            var remote = /^(?:[a-z]+:)\/\//;

            // Let remote links pass through
            if  (href.match(remote)) {
                return true;
            }

            // window.open is used as return true; didn't work reliable
            if (linkTarget === '_blank' || linkTarget === '_self') {
                window.open(href, linkTarget);
                return false;
            }

            // ignore multiselect table row clicks
            if ($a.is('tr') && $a.closest('table.multiselect').length > 0) {
                return;
            }

            // Handle all other links as XHR requests
            event.stopPropagation();
            event.preventDefault();

            // If link has hash tag...
            if (href.match(/#/)) {
                // ...it may be a menu section without a dedicated link.
                // Switch the active menu item:
                if (isMenuLink) {
                    $li = $a.closest('li');
                    $('#menu .active').removeClass('active');
                    $li.addClass('active');
                    if ($li.hasClass('hover')) {
                        $li.removeClass('hover');
                    }
                }
                if (href === '#') {
                    // Allow to access dropdown menu by keyboard
                    if ($a.hasClass('dropdown-toggle')) {
                        $a.closest('li').toggleClass('hover');
                    }
                    // Ignore link, no action
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

            // Menu links should remove all but the first layout column
            if (isMenuLink) {
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
                    if ($el.closest('#col2').length) {
                        this.icinga.ui.moveToLeft();
                    }
                    targetId = 'col2';
                    $target = $('#' + targetId);
                } else if (targetId === '_self') {
                    $target = $el.closest('.container');
                    targetId = $target.attr('id');
                } else if (targetId === '_main') {
                    targetId = 'col1';
                    $target = $('#' + targetId);
                    icinga.ui.layout1col();
                } else {
                    $target = $('#' + targetId);
                }

            }

            // Hardcoded layout switch unless columns are dynamic
            if ($target.attr('id') === 'col2') {
                icinga.ui.layout2col();
            }

            return $target;
        },

    /*
        hrefIsHashtag: function(href) {
            // WARNING: IE gives full URL :(
            // Also it doesn't support negativ indexes in substr
            return href.substr(href.length - 1, 1) == '#';
        },
    */

        unbindGlobalHandlers: function () {
            $(window).off('resize', this.onWindowResize);
            $(window).off('load', this.onLoad);
            $(window).off('unload', this.onUnload);
            $(window).off('beforeunload', this.onUnload);
            $(document).off('scroll', '.container', this.onContainerScroll);
            $(document).off('click', 'a', this.linkClicked);
            $(document).off('click', 'table.action tr[href]', this.rowSelected);
            $(document).off('click', 'table.action tr a', this.rowSelected);
            $(document).off('submit', 'form', this.submitForm);
            $(document).off('click', 'button', this.submitForm);
            $(document).off('change', 'form select.autosubmit', this.submitForm);
            $(document).off('mouseenter', '.historycolorgrid td', this.historycolorgridHover);
            $(document).off('mouseleave', '.historycolorgrid td', this.historycolorgidUnhover);
            $(document).off('mouseenter', 'li.dropdown', this.dropdownHover);
            $(document).off('mouseleave', 'li.dropdown', this.dropdownLeave);
            $(document).off('click', 'div.tristate .tristate-dummy', this.clickTriState);
        },

        destroy: function() {
            // This is gonna be hard, clean up the mess
            this.unbindGlobalHandlers();
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
