/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    /**
     * Initialize container data
     *
     * @param {event} e - Event
     */
    function onLayoutRendered(e) {
        var _this = e.data.self;
        if (!_this.layoutRendered) {
            console.log('layout render');
            _this.containerData = {};
            $('#col1, #col2').each( function() {
                var $container = $(this);
                var containerData = {
                    breakIndex: false,
                    breakPoints: [],
                    isToRender: true
                };
                _this.containerData[$container.attr('id')] = containerData;
            });
            _this.layoutRendered = true;
        }
    }

    /**
     * Flag container as being rendered
     *
     * @param {event} e - Event
     */
    function onRendered(e) {
        console.log(e.type);
        var _this = e.data.self;
        var $container = $(this);
        _this.containerData[$container.attr('id')].isToRender = true;
    }

    /**
     * Cache break points for #col1 and #col2
     *
     * @param {event} e - Event
     */
    function onWindowResized(e) {
        console.log(e.type);
        var _this = e.data.self;
        $('#col1, #col2').each(function() {
            var $this = $(this);
            cacheBreakpoints($this, _this);
            updateBreakIndex($this, _this);
        });
    }

    /**
     * Update container's break index if it has been already rendered
     *
     * @param {event} e - Event
     */
    function onFixControls(e) {
        console.log(e.type);
        var $container = $(this);
        var _this = e.data.self;
        $('#col1, #col2').each(function() {
            var $container = $(this);
            if (_this.containerData && _this.containerData[$container.attr('id')] && _this.containerData[$container.attr('id')].isToRender) {
                console.log("fix", $container, $container.find('.tabs').length > 0);
                if ($container.find('.tabs').length > 0) {
                    console.log('fix 2', $container);
                    cacheBreakpoints($container, _this);
                    updateBreakIndex($container, _this);
                }
            }
        });
    }

    /**
     * Cache tab break points in container
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {object} e - The behavior
     */
    function cacheBreakpoints($container, e) {
        console.log("cache");
        var containerData = {};
        var w = $container.find('.dropdown-nav-item').outerWidth(true)+1;
        containerData.breakPoints = [];
        $container.find('.tabs').not('.cloned').show().children('li').not('.dropdown-nav-item').each(function() {
            containerData.breakPoints.push(w += $(this).outerWidth(true) + 1);
        });
        e.containerData[$container.attr('id')] = containerData;
        e.containerData[$container.attr('id')].isToRender = true;
    }

    /**
     * Check Breakpoints and accordingly set the breakIndex
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {object} e - The behavior
     */
    function updateBreakIndex($container, e) {
        console.log("update");
        var b = false;
        var breakPoints = e.containerData[$container.attr('id')].breakPoints;
        for (var i = 0; i < breakPoints.length; i++) {
            if ( breakPoints[i] > $container.find('.tabs').width()) {
                b = i;
                break;
            }
        }
        setBreakIndex($container, b, e);
    }

    /**
     * Set the breakIndex and if value has changed render Tabs
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {int} newIndex - The index to be set
     *
     * @param {object} e - The behavior
     */
    function setBreakIndex($container, newIndex, e) {
        var containerData = e.containerData[$container.attr('id')];
        console.log('old : new', containerData.breakIndex, newIndex)
        if (newIndex === containerData.breakIndex) {
            return;
        } else {
            e.containerData[$container.attr('id')].breakIndex = newIndex;
            renderTabs($container, e);
        }
    }

    /**
     * Render Tabs of a container according to the updated breakIndex
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {object} e - The behavior
     */
    function renderTabs($container, e) {
        console.log("render tabs", $container);
        var breakIndex = e.containerData[$container.attr('id')].breakIndex;

        $container.find('.tabs.cloned').remove();
        if (breakIndex) {
            var $tabsClone = $container.find('.tabs').not('.cloned').hide().clone().addClass('cloned');

            // if not exists, create dropdown
            var $dropdown = null;
            if ( $tabsClone.children('.dropdown-nav-item').length > 0 ) {
                $dropdown = $tabsClone.children('.dropdown-nav-item');
            } else {
                $dropdown = $('<li class="dropdown-nav-item"><a href="#" class="dropdown-toggle" title="Dropdown menu" aria-label="Dropdown menu"><i aria-hidden="true" class="icon-down-open"></i></a><ul class="nav"></ul></li>');
                $tabsClone.append($dropdown);
            } // END if not exists, create dropdown

            // insert tab items into dropdown
            var l = $tabsClone.children('li').not('.dropdown-nav-item').length;
            for (var i = breakIndex; i < l; i++) {
                var $tab = $($tabsClone.children('li').not('.dropdown-nav-item').get(i));

                $dropdown.children('ul').append($tab.clone());
                $tab.hide();
            } // END insert tab items into dropdown

            $container.find('.tabs').not('.cloned').hide();
            $container.find('.controls').prepend($tabsClone.show());
        } else {
            //breakIndex false: No need for cloned tabs
            $container.find('.tabs').not('.cloned').show();
        }
        e.containerData[$container.attr('id')].isToRender = false;
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for managing tab bar width for variable screen width
     *
     * The ResponsiveTabBar will wrap tabs in a dropdown if the containing
     * tab bar becomes insufficient
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var ResponsiveTabBar = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#layout', onLayoutRendered, this);
        this.on('rendered', '#col1, #col2', onRendered, this);
        this.on('fix-controls', '#col1, #col2', onFixControls, this);
        $(window).resize({self: this}, onWindowResized);
    };

    ResponsiveTabBar.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ResponsiveTabBar = ResponsiveTabBar;
})(Icinga, jQuery);
