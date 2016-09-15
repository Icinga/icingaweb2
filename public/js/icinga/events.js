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
        this.initializeModules = true;
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
        applyHandlers: function (event) {
            var $target = $(event.target);
            var _this = event.data.self;
            var icinga = _this.icinga;

            if (! icinga) {
                // Attempt to catch a rare error, race condition, whatever
                console.log('Got no icinga in applyHandlers');
                return;
            }

            if (_this.initializeModules) {
                var loaded = false;
                var moduleName = $target.data('icingaModule');
                if (moduleName) {
                    if (icinga.hasModule(moduleName) && !icinga.isLoadedModule(moduleName)) {
                        loaded |= icinga.loadModule(moduleName);
                    }
                }

                $('.icinga-module', $target).each(function(idx, mod) {
                    moduleName = $(mod).data('icingaModule');
                    if (icinga.hasModule(moduleName) && !icinga.isLoadedModule(moduleName)) {
                        loaded |= icinga.loadModule(moduleName);
                    }
                });

                if (loaded) {
                    // Modules may register their own handler for the 'renderend' event
                    // so we need to ensure that it is called the first time they are
                    // initialized
                    event.stopImmediatePropagation();
                    _this.initializeModules = false;

                    var $container = $target.closest('.container');
                    if (! $container.length) {
                        // The page obviously got loaded for the first time,
                        // so we'll trigger the event for all containers
                        $container = $('.container');
                    }

                    $container.trigger('rendered');

                    // But since we're listening on this event by ourself, we'll have
                    // to abort our own processing as we'll process it twice otherwise
                    return false;
                }
            } else {
                _this.initializeModules = true;
            }

            $('.dashboard > div', $target).each(function(idx, el) {
                var $element = $(el);
                var $url = $element.data('icingaUrl');
                if (typeof $url !== 'undefined') {
                    icinga.loader.loadUrl($url, $element).autorefresh = true;
                }
            });

            var $searchField = $('#menu input.search', $target);
            // Remember initial search field value if any
            if ($searchField.length && $searchField.val().length) {
                _this.searchValue = $searchField.val();
            }
        },

        /**
         * Global default event handlers
         */
        applyGlobalDefaults: function () {
            // Apply element-specific behavior whenever the layout is rendered
            // Note: It is important that this is the first handler for this event!
            $(document).on('rendered', { self: this }, this.applyHandlers);

            $.each(this.icinga.behaviors, function (name, behavior) {
                behavior.bind($(document));
            });

            // We catch resize events
            $(window).on('resize', { self: this.icinga.ui }, this.icinga.ui.onWindowResize);

            // Trigger 'rendered' event also on page loads
            $(window).on('load', { self: this }, this.onLoad);

            // Destroy Icinga, clean up and interrupt pending requests on unload
            $( window ).on('unload', { self: this }, this.onUnload);
            $( window ).on('beforeunload', { self: this }, this.onUnload);

            // We catch scroll events in our containers
            $('.container').on('scroll', { self: this }, this.icinga.events.onContainerScroll);

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
            $('body').removeClass('loading');
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
            var _this = event.data.self;
            if ($('#menu input.search').val() === _this.searchValue) {
                return;
            }
            _this.searchValue = $('#menu input.search').val();
            return _this.autoSubmitForm(event);
        },

        rememberSubmitButton: function(e) {
            var $button = $(this);
            var $form = $button.closest('form');
            $form.data('submitButton', $button);
        },

        autoSubmitForm: function (event) {
            return event.data.self.submitForm(event, true);
        },

        /**
         *
         */
        submitForm: function (event, autosubmit) {
            var _this   = event.data.self;
            var icinga = _this.icinga;
            // .closest is not required unless subelements to trigger this
            var $form = $(event.currentTarget).closest('form');
            var url = $form.attr('action');
            var method = $form.attr('method');
            var encoding = $form.attr('enctype');
            var $button = $('input[type=submit]:focus', $form).add('button[type=submit]:focus', $form);
            var progressTimer;
            var $target;
            var data;

            var $rememberedSubmittButton = $form.data('submitButton');
            if (typeof $rememberedSubmittButton != 'undefined') {
                if ($form.has($rememberedSubmittButton)) {
                    $button = $rememberedSubmittButton;
                }
                $form.removeData('submitButton');
            }

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

            if (typeof encoding === 'undefined') {
                encoding = 'application/x-www-form-urlencoded';
            }

            if (typeof autosubmit === 'undefined') {
                autosubmit = false;
            }

            if ($button.length === 0) {
                $button = $('input[type=submit]', $form).add('button[type=submit]', $form).first();
            }

            if ($button.length) {
                // Activate spinner
                if ($button.hasClass('spinner')) {
                    $button.addClass('active');
                }

                $target = _this.getLinkTargetFor($button);
            } else {
                $target = _this.getLinkTargetFor($form);
            }

            if (! url) {
                // Use the URL of the target container if the form's action is not set
                url = $target.closest('.container').data('icinga-url');
            }

            icinga.logger.debug('Submitting form: ' + method + ' ' + url, method);

            if (method === 'GET') {
                var dataObj = $form.serializeObject();

                if (! autosubmit) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        dataObj[$button.attr('name')] = $button.attr('value');
                    }
                }

                url = icinga.utils.addUrlParams(url, dataObj);
            } else {
                if (encoding === 'multipart/form-data') {
                    if (typeof window.FormData === 'undefined') {
                        icinga.loader.submitFormToIframe($form, url, $target);

                        // Disable all form controls to prevent resubmission as early as possible.
                        // (This relies on native form submission, so using setTimeout is the only possible solution)
                        setTimeout(function () {
                            if ($target.attr('id') == $form.closest('.container').attr('id')) {
                                $form.find(':input:not(:disabled)').prop('disabled', true);
                            }
                        }, 0);

                        if (autosubmit) {
                            if ($button.length) {
                                // We're autosubmitting the form so the button has not been clicked, however,
                                // to be really safe, we're disabling the button explicitly, just in case..
                                $button.prop('disabled', true);
                            }

                            $form[0].submit(); // This should actually not trigger the onSubmit event, let's hope that this is true for all browsers..
                            event.stopPropagation();
                            event.preventDefault();
                            return false;
                        } else {
                            return true;
                        }
                    }

                    data = new window.FormData($form[0]);
                } else {
                    data = $form.serializeArray();
                }

                if (! autosubmit) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        if (encoding === 'multipart/form-data') {
                            data.append($button.attr('name'), $button.attr('value'));
                        } else {
                            data.push({
                                name: $button.attr('name'),
                                value: $button.attr('value')
                            });
                        }
                    }
                }
            }

            // Disable all form controls to prevent resubmission except for our search input
            // Note that disabled form inputs will not be enabled via JavaScript again
            if ($target.attr('id') == $form.closest('.container').attr('id')) {
                $form.find(':input:not(#search):not(:disabled)').prop('disabled', true);
            }

            // Show a spinner depending on how the form is being submitted
            if (autosubmit && typeof $el !== 'undefined' && $el.next().hasClass('spinner')) {
                $el.next().addClass('active');
            } else if ($button.length && $button.is('button') && $button.hasClass('animated')) {
                $button.addClass('active');
            } else if ($button.length && $button.attr('data-progress-label')) {
                var isInput = $button.is('input');
                if (isInput) {
                    $button.prop('value', $button.attr('data-progress-label') + '...');
                } else {
                    $button.html($button.attr('data-progress-label') + '...');
                }

                // Use a fixed width to prevent the button from wobbling
                $button.css('width', $button.css('width'));

                progressTimer = icinga.timer.register(function () {
                    var label = isInput ? $button.prop('value') : $button.html();
                    var dots = label.substr(-3);

                    // Using empty spaces here to prevent centered labels from wobbling
                    if (dots === '...') {
                        label = label.slice(0, -2) + '  ';
                    } else if (dots === '.. ') {
                        label = label.slice(0, -1) + '.';
                    } else if (dots === '.  ') {
                        label = label.slice(0, -2) + '. ';
                    }

                    if (isInput) {
                        $button.prop('value', label);
                    } else {
                        $button.html(label);
                    }
                }, null, 100);
            } else if ($button.length && $button.next().hasClass('spinner')) {
                $('i', $button.next()).addClass('active');
            } else if ($form.attr('data-progress-element')) {
                var $progressElement = $('#' + $form.attr('data-progress-element'));
                if ($progressElement.length) {
                    if ($progressElement.hasClass('spinner')) {
                        $('i', $progressElement).addClass('active');
                    } else {
                        $('i.spinner', $progressElement).addClass('active');
                    }
                }
            }

            var req = icinga.loader.loadUrl(url, $target, data, method);
            req.forceFocus = autosubmit ? $(event.currentTarget) : $button.length ? $button : null;
            req.progressTimer = progressTimer;

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
            if (href.match(/^(?:(?:mailto|javascript|data):|[a-z]+:\/\/)/)) {
                event.stopPropagation();
                return true;
            }

            // Special checks for link clicks in action tables
            if (! $a.is('tr[href]') && $a.closest('table.action').length > 0) {

                // ignoray clicks to ANY link with special key pressed
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
                $target = _this.getLinkTargetFor($a);

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
                $target = _this.getLinkTargetFor($a);
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
                    this.icinga.ui.layout1col();
                } else {
                    $target = $('#' + targetId);
                    if (! $target.length) {
                        this.icinga.logger.warn('Link target "#' + targetId + '" does not exist in DOM.');
                    }
                }

            }

            // Hardcoded layout switch unless columns are dynamic
            if ($target.attr('id') === 'col2') {
                this.icinga.ui.layout2col();
            }

            return $target;
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
        },

        destroy: function() {
            // This is gonna be hard, clean up the mess
            this.unbindGlobalHandlers();
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
