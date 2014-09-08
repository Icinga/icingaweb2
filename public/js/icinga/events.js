// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga.Events
 *
 * Event handlers
 */
(function (Icinga, $) {

    'use strict';

    var activeMenuId;

    var mouseX, mouseY;

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

            $('input.autofocus', el).focus();

            // replace all sparklines
            $('span.sparkline', el).each(function(i, element) {
                // read custom options
                var $spark            = $(element);
                var labels            = $spark.attr('labels').split('|');
                var formatted         = $spark.attr('formatted').split('|');
                var tooltipChartTitle = $spark.attr('sparkTooltipChartTitle') || '';
                var format            = $spark.attr('tooltipformat');
                var hideEmpty         = $spark.attr('hideEmptyLabel') === 'true';
                $spark.sparkline(
                    'html',
                    {
                        enableTagOptions: true,
                        tooltipFormatter: function (sparkline, options, fields) {
                            var out       = format;
                            if (hideEmpty && fields.offset === 3) {
                                return '';
                            }
                            var replace   = {
                                title:     tooltipChartTitle,
                                label:     labels[fields.offset] ? labels[fields.offset] : fields.offset,
                                formatted: formatted[fields.offset] ? formatted[fields.offset] : '',
                                value:     fields.value,
                                percent:   Math.round(fields.percent * 100) / 100
                            };
                            $.each(replace, function(key, value) {
                                out = out.replace('{{' + key + '}}', value);
                            });
                            return out;
                        }
                });
            });
            var searchField = $('#menu input.search', el);
            // Remember initial search field value if any
            if (searchField.length && searchField.val().length) {
                this.searchValue = searchField.val();
            }

            $('[title]').each(function () {
                var $el = $(this);
                $el.attr('title', $el.data('title-rich') || $el.attr('title'));
            });
            $('svg rect.chart-data[title]', el).tipsy({ gravity: 'se', html: true });
            $('.historycolorgrid a[title]', el).tipsy({ gravity: 's', offset: 2 });
            $('img.icon[title]', el).tipsy({ gravity: $.fn.tipsy.autoNS, offset: 2 });
            $('[title]', el).tipsy({ gravity: $.fn.tipsy.autoNS, delayIn: 500 });

            // migrate or remove all orphaned tooltips
            $('.tipsy').each(function () {
                var arrow = $('.tipsy-arrow', this)[0];
                if (!icinga.utils.elementsOverlap(arrow, $('#main')[0])) {
                    $(this).remove();
                    return;
                }
                if (!icinga.utils.elementsOverlap(arrow, el)) {
                    return;
                }
                var title = $(this).find('.tipsy-inner').html();
                var atMouse = document.elementFromPoint(mouseX, mouseY);
                var nearestTip = $(atMouse)
                    .closest('[original-title="' + title + '"]')[0];
                if (nearestTip) {
                    var tipsy = $.data(nearestTip, 'tipsy');
                    tipsy.$tip = $(this);
                    $.data(this, 'tipsy-pointee', nearestTip);
                } else {
                    // doesn't match delete
                    $(this).remove();
                }
            });

            // restore menu state
            if (activeMenuId) {
                $('#menu .active').removeClass('active');
                $('#' + activeMenuId).addClass('active');
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
            $('.container').on('scroll', { self: this }, this.icinga.events.onContainerScroll);

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
            $(document).on('change', 'form input.autosubmit', { self: this }, this.autoSubmitForm);

            $(document).on('keyup', '#menu input.search', {self: this}, this.autoSubmitSearch);

            $(document).on('mouseenter', '.historycolorgrid td', this.historycolorgridHover);
            $(document).on('mouseleave', '.historycolorgrid td', this.historycolorgidUnhover);
            $(document).on('mouseenter', 'li.dropdown', this.dropdownHover);
            $(document).on('mouseleave', 'li.dropdown', {self: this}, this.dropdownLeave);

            $(document).on('mouseenter', '#menu > ul > li', { self: this }, this.menuTitleHovered);
            $(document).on('mouseleave', '#sidebar', { self: this }, this.leaveSidebar);
            $(document).on('click', '.tree .handle', { self: this }, this.treeNodeToggle);

            // Toggle all triStateButtons
            $(document).on('click', 'div.tristate .tristate-dummy', { self: this }, this.clickTriState);

            // TBD: a global autocompletion handler
            // $(document).on('keyup', 'form.auto input', this.formChangeDelayed);
            // $(document).on('change', 'form.auto input', this.formChanged);
            // $(document).on('change', 'form.auto select', this.submitForm);

            $(document).on('mousemove', function (event) {
                mouseX = event.pageX;
                mouseY = event.pageY;
            });
        },

        menuTitleHovered: function (event) {
            var $li = $(this),
                delay = 800,
                self = event.data.self;

            if ($li.hasClass('active')) {
                $li.siblings().removeClass('hover');
                return;
            }
            if ($li.children('ul').children('li').length === 0) {
                return;
            }
            if ($('#menu').scrollTop() > 0) {
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

        leaveSidebar: function (event) {
            var $sidebar = $(this),
                $li = $sidebar.find('li.hover'),
                self = event.data.self;
            if (! $li.length) {
                $('#layout').removeClass('hoveredmenu');
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

        dropdownLeave: function (event) {
            var $li = $(this),
                self = event.data.self;
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
            var self = event.data.self;
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
            } else if ($trs.length === 1) {
                // display a single row
                $tr = $trs.first();
                icinga.loader.loadUrl($tr.attr('href'), $target);
                icinga.ui.storeSelectionData($tr.attr('href'));
            } else {
                // display nothing
                if ($target.attr('id') === 'col2') {
                    icinga.ui.layout1col();
                }
                icinga.ui.storeSelectionData(null);
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

            // Ignore form elements in action rows
            if ($(event.target).is('input') || $(event.target).is('button')) {
                return;
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
                    activeMenuId = $($li).attr('id');
                    if ($li.hasClass('hover')) {
                        $('#menu .active').removeClass('active');
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

            if (isMenuLink) {
                // update target url of the menu container to the clicked link
                var menuDataUrl = icinga.utils.parseUrl($('#menu').data('icinga-url'));
                menuDataUrl = icinga.utils.addUrlParams(menuDataUrl.path, { url: href });
                $('#menu').data('icinga-url', menuDataUrl);

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
