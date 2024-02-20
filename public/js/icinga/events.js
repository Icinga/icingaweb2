/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
        this.searchTimer = null;
    };

    Icinga.Events.prototype = {

        /**
         * Icinga will call our initialize() function once it's ready
         */
        initialize: function () {
            this.applyGlobalDefaults();
        },

        /**
         * Global default event handlers
         */
        applyGlobalDefaults: function () {
            $(document).on('visibilitychange', { self: this }, this.onVisibilityChange);

            $.each(this.icinga.behaviors, function (name, behavior) {
                behavior.bind($(document));
            });

            // Initialize module javascript (Applies only to module.js code)
            this.icinga.ensureSubModules(document);

            // We catch resize events
            $(window).on('resize', { self: this.icinga.ui }, this.icinga.ui.onWindowResize);

            // Trigger 'rendered' event also on page loads
            $(document).on('icinga-init', { self: this }, this.onInit);

            // Destroy Icinga, clean up and interrupt pending requests on unload
            $( window ).on('unload', { self: this }, this.onUnload);
            $( window ).on('beforeunload', { self: this }, this.onUnload);

            // Remove notifications on click
            $(document).on('click', '#notifications li', function () { $(this).remove(); });

            // We want to catch each link click
            $(document).on('click', 'a', { self: this }, this.linkClicked);
            $(document).on('click', 'tr[href]', { self: this }, this.linkClicked);

            $(document).on('click', 'input[type="submit"], button[type="submit"]', this.rememberSubmitButton);
            // We catch all form submit events
            $(document).on('submit', 'form', { self: this }, this.submitForm);

            // We support an 'autosubmit' class on dropdown form elements
            $(document).on('change', 'form select.autosubmit', { self: this }, this.autoSubmitForm);
            $(document).on('change', 'form input.autosubmit', { self: this }, this.autoSubmitForm);

            // Automatically check a radio button once a specific input is focused
            $(document).on('focus', 'form select[data-related-radiobtn]', { self: this }, this.autoCheckRadioButton);
            $(document).on('focus', 'form input[data-related-radiobtn]', { self: this }, this.autoCheckRadioButton);

            $(document).on('rendered', '#menu', { self: this }, this.onRenderedMenu);
            $(document).on('keyup', '#search', { self: this }, this.autoSubmitSearch);

            $(document).on('click', '.tree .handle', { self: this }, this.treeNodeToggle);

            $(document).on('click', '#search + .search-reset', this.clearSearch);

            $(document).on('rendered', '.container', { self: this }, this.loadDashlets);

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

        onInit: function (event) {
            $('body').removeClass('loading');

            // Trigger the initial `rendered` events
            $('.container').trigger('rendered');

            // Additionally trigger a `rendered` event on the layout, some behaviors may
            // want to differentiate whether a container or the entire layout is rendered
            $('#layout').trigger('rendered');
        },

        onUnload: function (event) {
            var icinga = event.data.self.icinga;
            icinga.logger.info('Unloading Icinga');
            icinga.destroy();
        },

        onVisibilityChange: function (event) {
            var icinga = event.data.self.icinga;

            if (document.visibilityState === undefined || document.visibilityState === 'visible') {
                icinga.loader.autorefreshSuspended = false;
                icinga.logger.debug('Page visible, enabling auto-refresh');
            } else {
                icinga.loader.autorefreshSuspended = true;
                icinga.logger.debug('Page invisible, disabling auto-refresh');
            }
        },

        autoCheckRadioButton: function (event) {
            var $input = $(event.currentTarget);
            var $radio = $('#' + $input.attr('data-related-radiobtn'));
            if ($radio.length) {
                $radio.prop('checked', true);
            }
            return true;
        },

        onRenderedMenu: function(event) {
            var _this = event.data.self;
            var $target = $(event.target);

            var $searchField = $target.find('input.search');
            // Remember initial search field value if any
            if ($searchField.length && $searchField.val().length) {
                _this.searchValue = $searchField.val();
            }
        },

        autoSubmitSearch: function(event) {
            var _this = event.data.self;
            var $searchField = $(event.target);

            if ($searchField.val() === _this.searchValue) {
                return;
            }
            _this.searchValue = $searchField.val();

            if (_this.searchTimer !== null) {
                clearTimeout(_this.searchTimer);
                _this.searchTimer = null;
            }
            var _event = $.extend({}, event);  // event seems gc'd once the timeout is over
            _this.searchTimer = setTimeout(function () {
                _this.submitForm(_event, $searchField);
                _this.searchTimer = null;
            }, 500);
        },

        loadDashlets: function(event) {
            var _this = event.data.self;
            var $target = $(event.target);

            if ($target.children('.dashboard').length) {
                $target.find('.dashboard > .container').each(function () {
                    var $dashlet = $(this);
                    var url = $dashlet.data('icingaUrl');
                    if (typeof url !== 'undefined') {
                        _this.icinga.loader.loadUrl(url, $dashlet).autorefresh = true;
                    }
                });
            }
        },

        rememberSubmitButton: function(e) {
            var $button = $(this);
            var $form = $button.closest('form');
            $form.data('submitButton', $button);
        },

        autoSubmitForm: function (event) {
            let form = event.currentTarget.form;

            if (form.closest('[data-no-icinga-ajax]')) {
                return;
            }

            form.dispatchEvent(new CustomEvent('submit', {
                cancelable: true,
                bubbles: true,
                detail: { submittedBy: event.currentTarget }
            }));
        },

        /**
         *
         */
        submitForm: function (event, $autoSubmittedBy) {
            var _this   = event.data.self;

            // .closest is not required unless subelements to trigger this
            var $form = $(event.currentTarget).closest('form');

            if ($form.closest('[data-no-icinga-ajax]').length > 0) {
                return true;
            }
            
            var $button;
            var $rememberedSubmittButton = $form.data('submitButton');
            if (typeof $rememberedSubmittButton != 'undefined') {
                if ($form.has($rememberedSubmittButton)) {
                    $button = $rememberedSubmittButton;
                }
                $form.removeData('submitButton');
            }

            if (typeof $button === 'undefined') {
                var $el;

                if (event.originalEvent && event.originalEvent.submitter) {
                    $el = $(event.originalEvent.submitter);
                } else if (typeof event.originalEvent !== 'undefined'
                    && typeof event.originalEvent.explicitOriginalTarget === 'object') { // Firefox
                    $el = $(event.originalEvent.explicitOriginalTarget);
                    _this.icinga.logger.debug('events/submitForm: Button is event.originalEvent.explicitOriginalTarget');
                } else {
                    $el = $(event.currentTarget);
                    _this.icinga.logger.debug('events/submitForm: Button is event.currentTarget');
                }

                if ($el && ($el.is('input[type=submit]') || $el.is('button[type=submit]'))) {
                    $button = $el;
                } else {
                    _this.icinga.logger.debug(
                        'events/submitForm: Can not determine submit button, using the first one in form'
                    );
                }
            }

            if (! $autoSubmittedBy && event.detail && event.detail.submittedBy) {
                $autoSubmittedBy = $(event.detail.submittedBy);
            }

            _this.icinga.loader.submitForm($form, $autoSubmittedBy, $button);

            event.stopPropagation();
            event.preventDefault();
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
         * Someone clicked a link or tr[href]
         */
        linkClicked: function (event) {
            var _this   = event.data.self;
            var icinga = _this.icinga;
            var $a = $(this);
            var $eventTarget = $(event.target);
            var href = $a.attr('href');
            var linkTarget = $a.attr('target');
            var $target;
            var formerUrl;

            if (! href) {
                return;
            }

            if (href.match(/^(?:(?:mailto|javascript|data):|[a-z]+:\/\/)/)) {
                event.stopPropagation();
                return true;
            }

            if ($a.closest('[data-no-icinga-ajax]').length > 0) {
                return true;
            }

            // Check for ctrl or cmd click to open new tab unless clicking on a multiselect row
            if ((event.ctrlKey || event.metaKey) && href !== '#' && $a.is('a')) {
                window.open(href, linkTarget);
                return false;
            }

            // Special checks for link clicks in action tables
            if (! $a.is('tr[href]') && $a.closest('table.action').length > 0) {

                // ignore clicks to ANY link with special key pressed
                if ($a.closest('table.multiselect').length > 0 && (event.ctrlKey || event.metaKey || event.shiftKey)) {
                    return true;
                }

                // ignore inner links matching the row URL
                if ($a.attr('href') === $a.closest('tr[href]').attr('href')) {
                    return true;
                }
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
                && href.substr(1, 1) !== '!'
            ) {
                icinga.ui.focusElement(href.substr(1), $a.closest('.container'));
                return;
            }

            // activate spinner indicator
            if ($a.hasClass('spinner')) {
                $a.addClass('active');
            }

            // If link has hash tag...
            if (href.match(/#/)) {
                if (href === '#') {
                    if ($a.hasClass('close-container-control')) {
                        if (! icinga.ui.isOneColLayout()) {
                            var $cont = $a.closest('.container').first();
                            if ($cont.attr('id') === 'col1') {
                                icinga.ui.moveToLeft();
                                icinga.ui.layout1col();
                            } else {
                                icinga.ui.layout1col();
                            }
                            icinga.history.pushCurrentState();
                        }
                    }
                    return false;
                }
                $target = icinga.loader.getLinkTargetFor($a);

                formerUrl = $target.data('icingaUrl');
                if (typeof formerUrl !== 'undefined' && formerUrl.split(/#/)[0] === href.split(/#/)[0]) {
                    icinga.ui.focusElement(href.split(/#/)[1], $target);
                    $target.data('icingaUrl', href);
                    if (formerUrl !== href) {
                        icinga.history.pushCurrentState();
                    }
                    return false;
                }
            } else {
                $target = icinga.loader.getLinkTargetFor($a);
            }

            // Load link URL
            icinga.loader.loadUrl(href, $target);

            if ($a.closest('#menu').length > 0) {
                // Menu links should remove all but the first layout column
                icinga.ui.layout1col();
            }

            return false;
        },

        clearSearch: function (event) {
            $(event.target).parent().find('#search').attr('value', '');
        },

        unbindGlobalHandlers: function () {
            $.each(this.icinga.behaviors, function (name, behavior) {
                behavior.unbind($(document));
            });
            $(window).off('resize', this.onWindowResize);
            $(window).off('load', this.onLoad);
            $(window).off('unload', this.onUnload);
            $(window).off('beforeunload', this.onUnload);
            $(document).off('scroll', '.container', this.onContainerScroll);
            $(document).off('click', 'a', this.linkClicked);
            $(document).off('submit', 'form', this.submitForm);
            $(document).off('change', 'form select.autosubmit', this.submitForm);
            $(document).off('change', 'form input.autosubmit', this.submitForm);
            $(document).off('focus', 'form select[data-related-radiobtn]', this.autoCheckRadioButton);
            $(document).off('focus', 'form input[data-related-radiobtn]', this.autoCheckRadioButton);
            $(document).off('visibilitychange', this.onVisibilityChange);
        },

        destroy: function() {
            // This is gonna be hard, clean up the mess
            this.unbindGlobalHandlers();
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
