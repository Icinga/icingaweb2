/**
 * Icinga.Events
 *
 * Event handlers
 */
(function (Icinga, $) {

    'use strict';

    Icinga.Events = function (icinga) {
        this.icinga = icinga;
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

            $('.inlinepie', el).sparkline('html', {
                type:        'pie',
                sliceColors: ['#44bb77', '#ffaa44', '#ff5566', '#dcd'],
                width:       '2em',
                height:      '2em'
            });
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

            // We treat tr's with a href attribute like links
            $(document).on('click', ':not(table) tr[href]', { self: this }, this.linkClicked);

            // When tables have the class 'multiselect', multiple selection is possible.
            $(document).on('click', 'table tr[href]', { self: this }, this.rowSelected);

            $(document).on('click', 'button', { self: this }, this.submitForm);

            // We catch all form submit events
            $(document).on('submit', 'form', { self: this }, this.submitForm);

            // We support an 'autosubmit' class on dropdown form elements
            $(document).on('change', 'form select.autosubmit', { self: this }, this.autoSubmitForm);

            $(document).on('keyup', '#menu input.search', {self: this}, this.autoSubmitForm);

            $(document).on('mouseenter', '.historycolorgrid td', this.historycolorgridHover);
            $(document).on('mouseleave', '.historycolorgrid td', this.historycolorgidUnhover);
            $(document).on('mouseenter', 'li.dropdown', this.dropdownHover);
            $(document).on('mouseleave', 'li.dropdown', this.dropdownLeave);
            $(document).on('mouseenter', '#menu > ul > li', this.menuTitleHovered);
            $(document).on('mouseleave', '#sidebar', this.leaveSidebar);
            $(document).on('click', '.tree .handle', { self: this }, this.treeNodeToggle);


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
                if (! $li.is('li:hover')) {
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

        autoSubmitForm: function (event) {
            return event.data.self.submitForm(event, true);
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
            var $button = $('input[type=submit]:focus', $form);
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
            var data     = $table.data('icinga-multiselect-data').split(',');
            var multisel = $table.hasClass('multiselect');
            var url      = $table.data('icinga-multiselect-url');
            var $trs, $target;
            event.stopPropagation();
            event.preventDefault();
            if (icinga.events.handleExternalTarget($tr)) {
                // link handled externally
                return false;
            }
            if (!data) {
                icinga.logger.error('A table with multiselection must define the attribute "data-icinga-multiselect-data"');
                return;
            }
            if (!url) {
                icinga.logger.error('A table with multiselection must define the attribute "data-icinga-multiselect-url"');
                return;
            }

            // Update selection
            if (event.ctrlKey && multisel) {
                // multi selection
                if ($tr.hasClass('active')) {
                    $tr.removeClass('active');
                } else {
                    $tr.addClass('active');
                }
            } else if (event.shiftKey && multisel) {
                // range selection

                var $rows = $table.find('tr[href]'),
                    from, to;
                var selected = this;

                // TODO: find a better place for this
                $rows.find('td').attr('unselectable', 'on')
                    .css('user-select', 'none')
                    .css('-webkit-user-select', 'none')
                    .css('-moz-user-select', 'none')
                    .css('-ms-user-select', 'none');

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
            } else {
                // single selection
                if ($tr.hasClass('active')) {
                    return false;
                }
                $table.find('tr[href].active').removeClass('active');
                $tr.addClass('active');
            }
            $trs = $table.find('tr[href].active');

            // Update url
            $target = self.getLinkTargetFor($tr);
            if ($trs.length > 1) {
                // display multiple rows
                var query = icinga.events.selectionToQuery($trs, data, icinga);
                icinga.loader.loadUrl(url + '?' + query, $target);
            } else if ($trs.length === 1) {
                // display a single row
                icinga.loader.loadUrl($tr.attr('href'), $target);
            } else {
                // display nothing
                icinga.loader.loadUrl('#');
            }
            return false;
        },

        selectionToQuery: function ($selection, data, icinga) {
            var selections = [], queries = [];
            if ($selection.length === 0) {
                return '';
            }

            // read all current selections
            $selection.each(function(ind, selected) {
                var url    = $(selected).attr('href');
                var params = icinga.utils.parseUrl(url).params;
                var tuple  = {};
                for (var i = 0; i < data.length; i++) {
                    var key = data[i];
                    if (params[key]) {
                        tuple[key] = params[key];
                    }
                }
                selections.push(tuple);
            });

            // create new url
            if (selections.length < 2) {
                // single-selection
                $.each(selections[0], function(key, value){
                    queries.push(key + '=' + encodeURIComponent(value));
                });
            } else {
                // multi-selection
                $.each(selections, function(i, el){
                    $.each(el, function(key, value) {
                        queries.push(key + '[' + i + ']=' + encodeURIComponent(value));
                    });
                });
            }
            return queries.join('&');
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

            // TODO: Let remote links pass through. Right now they only work
            //       combined with target="_blank" or target="_self"
            // window.open is used as return true; didn't work reliable
            if (linkTarget === '_blank' || linkTarget === '_self') {
                window.open(href, linkTarget);
                return false;
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
            $(document).off('click', 'tr[href]', this.linkClicked);
            $(document).off('submit', 'form', this.submitForm);
            $(document).off('click', 'button', this.submitForm);
            $(document).off('change', 'form select.autosubmit', this.submitForm);
            $(document).off('mouseenter', '.historycolorgrid td', this.historycolorgridHover);
            $(document).off('mouseleave', '.historycolorgrid td', this.historycolorgidUnhover);
            $(document).off('mouseenter', 'li.dropdown', this.dropdownHover);
            $(document).off('mouseleave', 'li.dropdown', this.dropdownLeave);
        },

        destroy: function() {
            // This is gonna be hard, clean up the mess
            this.unbindGlobalHandlers();
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
