/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
define([
    'jquery',
    'vendor/jquery.sparkline.min',
    'logging',
    'icinga/behaviour',
    'icinga/util/async',
    'icinga/container',
    'modules/list'
], function ($,sparkline,log,behaviour,async,containerMgr, modules) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {
        var internalBehaviours = ['icinga/behaviour/actionTable','icinga/behaviour/mainDetail'];

        this.modules     = {};
        var failedModules = [];

        var initialize = function () {
            require(['modules/list']);
            enableDefaultBehaviour();

            containerMgr.registerAsyncMgr(async);
            containerMgr.initializeContainers(document);
            log.debug("Initialization finished");

            enableModules();
        };

        var enableDefaultBehaviour = function() {
            $.each(internalBehaviours,function(idx,behaviourImpl) {
                behaviour.enableBehaviour(behaviourImpl,log.error);
            });
        };

        var enableModules = function(moduleList) {
            moduleList = moduleList || modules;

            $.each(modules,function(idx,module) {
                if(module.behaviour) {
                    behaviour.enableBehaviour(module.name+"/"+module.name,function(error) {
                        failedModules.push({name: module.name,errorMessage: error});
                    });
                }
            });
        };

        var enableCb = function(behaviour) {
            behaviour.enable();
        };

        $(document).ready(initialize.bind(this));

        return {
            /**
             *
             */
            loadModule: function(blubb,bla) {
                behaviour.registerBehaviour(blubb,bla);
            } ,

            loadIntoContainer: function(ctr) {

            }

        };
    };
    return new Icinga();
});



        /**
         *
         */
    /*    prepareContainers: function ()
        {
            $('#icinga-main').attr(
                'icingaurl',
                window.location.pathname + window.location.search
            );
            return Icinga;
        },

        initializeTimer: function (interval)
        {
            // This currently messes up the frontend
            //Icinga.refresher = setInterval(Icinga.timer, interval);
            return Icinga;
        },

        module: function (name)
        {
            return Icinga.modules[name];
        },

        timer: function ()
        {
            $('.icinga-container[icingaurl]').each(function (idx, el)
            {
                el = $(el);
                // TODO: Find a better solution for this problem
                if (el.find('.icinga-container').length) {
                    return;
                }
                Icinga.loadUrl(el.attr('icingaurl'), el, el.data('icingaparams'));
            });
        },*/


        /**
         * Clicks on action table rows shall trigger the first link action found
         * in that row
         */
     /*   prepareActionRow: function (idx, el)
        {
            var a = $(el).find('a.row-action'),
                tr;
            if (a.length < 1) {
                a = $(el).find('a');
            }
            if (a.length < 1) { return; }
            tr = a.closest('tr');
            tr.attr('href', a.first().attr('href'));
            // $(a).first().replaceWith($(a).html());
            // tr.addClass('hasHref');
        },*/

        /**
         * Apply event handlers within a given parent node
         */
  /*      applyEventHandlers: function (parent)
        {
            // Icinga.debug('Applying event handlers');
            $('.dashboard.icinga-container', parent).each(function(idx, el) {
                Icinga.loadUrl($(el).attr('icingaurl'), $(el));
            });
            $('div[icingamodule]', parent).each(function (idx, el) {
                Icinga.loadModule($(el).attr('icingamodule'), el);
            });
            $('table.action tbody tr', parent).each(Icinga.prepareActionRow);
<<<<<<< HEAD:public/js/icinga/icinga.js
            $('a[title], span[title], td[title], img[title]', parent).qtip()
                .each(Icinga.fixQtippedElement);*/
/*            $('.inlinepie', parent).sparkline('html', {
=======
            $('*[title]').tooltip({placement:"bottom",container: "body"});
            $('.inlinepie', parent).sparkline('html', {
>>>>>>> 3845f64dd2380974633fad30c3160944e9bff1d2:public/js/icinga.js
                type:        'pie',
                sliceColors: ['#0c0', '#f80', '#c00', '#dcd'],
                width:       '16px',
                height:      '16px'
            });*//*
            $('.inlinebullet', parent).sparkline('html', {
                type:        'bullet',
                targetColor: '#000',
                rangeColors: ['#0c0', '#f80', '#c00'],
                width:       '16px',
                height:      '16px'
            });
            // Icinga.debug('Applying event handlers done');
            return Icinga;
        },
*/
        /**
         * Apply some global event handlers
         */
  /*      applyGlobalEventHandlers: function ()
        {
            // TODO: replace document with deeper parent elements once they are
            //       defined
            $(document).on('click', 'a', Icinga.linkClicked);
            $(document).on('click', 'tr[href]', Icinga.linkClicked);
            $(document).on('keyup', 'form.auto input', Icinga.formChangeDelayed);
            $(document).on('change', 'form.auto input', Icinga.formChanged);
            $(document).on('change', 'form.auto select', Icinga.formChanged);
            $(document).on('submit', 'form', Icinga.submitForm);
            return Icinga;
        },
        applyModuleEventHandlers: function (name, el)
        {
            Icinga.debug('Applying handlers for ' + name);
            $.each(Icinga.moduleHandlers[name], function (idx, event) {
                if (event[1] === 'each') {
                    $(event[0], $(el)).each(event[2]);
                } else {
                    $(event[0], $(el)).on(event[1], event[2]);
                }
            });
        },
        loadModule: function (name, el)
        {
            if (typeof Icinga.modules[name] !== 'undefined')
            {
                Icinga.applyModuleEventHandlers(name, $(el));
                return;
            }
            try {
                require([name], function (module) {
                    Icinga.modules[name] = module;
                    Icinga.moduleHandlers[name] = [];
                    if (typeof module.eventHandlers !== 'undefined') {
                        $.each(module.eventHandlers, function (filter, events) {
                            $.each(events, function (event, handle) {
                                var eventHandle = Icinga.modules[name][handle];
                                Icinga.moduleHandlers[name].push([
                                    filter,
                                    event,
                                    eventHandle
                                ]);
                            });
                        });
                    }
                    Icinga.applyModuleEventHandlers(name, $(el));
                    Icinga.debug('Module "' + name + '" has been loaded');
                });
            } catch (e) {
                Icinga.debug(
                    'Loading module "' + name + '" failed: ' +
                        e.name + "\n" + e.message
                );
                return;
            }
        },
*/
        /**
         * Load CSS file if not already done so.
         *
         * This is an unfinished prototype, we have to make sure to not load
         * the same file multiple times
         *
         * TODO: Finish it, remember/discover what has been loaded, to it the
         *       jQuery way
         */
 /*       requireCss: function (url)
        {
            Icinga.debug('Should load CSS file: ' + url);
        //    var link = document.createElement("link");
        //    link.type = "text/css";
        //    link.rel = "stylesheet";
        //    link.href = url;
        //    document.getElementsByTagName("head")[0].appendChild(link);
        },
*/
        /**
         * Load the given URL to the given target
         *
         * @param {string} url    URL to be loaded
         * @param {object} target Target element
         */
  /*      loadUrl: function (url, target, data)
        {
            var req = $.ajax({
                type   : 'POST',
                url    :  url,
                data   : data,
                headers: { 'X-Icinga-Accept': 'text/html' }
            });
            req.done(Icinga.handleResponse);
            req.IcingaTarget = target;
            req.fail(Icinga.handleFailure);
            return req;
        },
*/
        /**
         * Create an URL relative to the Icinga base Url, still unused
         *
         * @param {string} url Relative url
         */
  /*      url: function (url)
        {
            return base_url + url;
        },
*/
        /**
         * Smoothly render given HTML to given container
         */
  /*      renderContentToContainer: function (content, container)
        {
            Icinga.disableQtips();
            Icinga.debug('fire');
            container.html(content);
            if (container.attr('id') === 'icinga-detail') {
                container.closest('.layout-main-detail').removeClass('collapsed');
            }
            Icinga.applyEventHandlers(container);

        },
*/
        /**
         * Handle successful XHR response
         */
  /*      handleResponse: function (data, textStatus, jqXHR)
        {
            Icinga.debug('Got response: ' + this.url);
            jqXHR.IcingaTarget.attr('icingaurl', this.url);
            Icinga.renderContentToContainer(jqXHR.responseText, jqXHR.IcingaTarget);
        },
*/
        /**
         * Handle failed XHR response
         */
  /*      handleFailure: function (jqXHR, textStatus, errorThrown)
        {
            if (jqXHR.status > 0) {
                Icinga.debug(jqXHR.responseText.slice(0, 100));
                Icinga.renderContentToContainer(
                    '<h1>' + jqXHR.status + ' ' + errorThrown + '</h1> ' +
                        jqXHR.responseText,
                    jqXHR.IcingaTarget
                );

                // Header example:
                // Icinga.debug(jqXHR.getResponseHeader('X-Icinga-Redirect'));
            } else {
                if (errorThrown === 'abort') {
                    Icinga.debug('Request to ' + this.url + ' has been aborted');
                } else {
                    Icinga.debug('Failed to contact web server');
                }
            }
        },
*/
        /**
         * A link has been clicked. Try to find out it's target and fire the XHR
         * request
         */
  /*      linkClicked: function (event)
        {
            event.stopPropagation();
            var target = event.currentTarget,
                href = $(target).attr('href'),
                destination;

            if ($(target).closest('.pagination').length) {
                Icinga.debug('Pagination link clicked');
                destination = $(target).closest('.icinga-container');
            } else if ($(target).closest('.nav-tabs').length) {
                Icinga.debug('Nav tab link clicked');
                destination = $(target).closest('.icinga-container');
            } else if ($(target).closest('table.action').length ||
                // TODO: define just one class name instead of this list
                $(target).closest('table.pivot').length ||
                $(target).closest('.bpapp').length ||
                $(target).closest('.dashboard.icinga-container').length)
            {
                destination = $('#icinga-detail');
                Icinga.debug('Clicked an action table / pivot / bpapp / dashboard link');
            } else  {
                Icinga.debug('Something else clicked');
                // target = $(target).closest('.icinga-container');
                destination = $(target).closest('.icinga-container');
                if (!destination.length) {
                    destination = $('#icinga-main');
                }
            }

            Icinga.loadUrl(href, destination);
            return false;
        },
*/
        /* BEGIN form handling, still unfinished */
  /*      formChanged: function (event)
        {
            // TODO: event.preventDefault();
            // TODO: event.stopPropatagion();
            if (Icinga.load_form !== false) {
                // Already loading. TODO: make it multi-form-aware
                // TODO: shorter timeout, but clear not before ajax call finished
                return;
            }
            var target = event.currentTarget,
                form   = $(target).closest('form');
            Icinga.load_form = form;
            Icinga.fireFormLoader();
        },

        formChangeDelayed: function (event)
        {
            if (Icinga.load_form !== false) {
                // Already loading. TODO: make it multi-form-aware
                // TODO: shorter timeout, but clear not before ajax call finished
                return;
            }
            var target = event.currentTarget,
                form   = $(target).closest('form');
            Icinga.load_form = form;
            setTimeout(Icinga.fireFormLoader, 200);
        },

        fireFormLoader: function ()
        {
            if (Icinga.load_form === false) {
                return;
            }
            // Temporarily hardcoded for top search:
            // Icinga.loadUrl(Icinga.load_form.attr('action') + '?' + Icinga.load_form.serialize(), $('#icinga-main'));
            Icinga.debug(Icinga.load_form);
            if ($('#icinga-main').find('.dashboard.icinga-container').length) {
                $('#icinga-main .dashboard.icinga-container').each(function (idx, el) {
                    Icinga.loadSearch($(el));
                });
            } else {
                $('#icinga-main').each(function (idx, el) {
                    Icinga.loadSearch($(el));
                });
            }
            Icinga.load_form = false;

        },
        loadSearch: function(target)
        {
            var url = target.attr('icingaurl');
            var params = url.split('?')[1] || '';
            url = url.split('?')[0];
            var param_list = params.split('&');
            params = {};
            var i, pairs;
            for (i = 0; i < param_list.length; i++) {
                pairs = param_list[i].split('=');
                params[pairs[0]] = pairs[1]; 
            }
            var searchstring = $('input[name=search]', Icinga.load_form).val();
            params['search'] = searchstring || '';
            params = '?' + $.param(params);
            Icinga.loadUrl(url + params, target);
        },
        submitForm: function (event)
        {
            event.stopPropagation();
            var form = $(event.currentTarget);
            Icinga.loadUrl(form.attr('action'), $('#icinga-main'), form.serializeArray());
            return false;
        },*/
        /* END of form handling */
