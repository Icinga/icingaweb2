/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    /**
     * Initialize container break points, if event is not bubbling
     *
     * @param {event} e - Event
     */
    function onInitialize(e) {
        if (this !== e.target) {
            return;
        }
        var _this = e.data.self;
        _this.containerData = {};
        $('#col1, #col2').each( function() {
            var $this = $(this);
            if ($this.find('.tabs').length) {
                var containerData = {
                    breakIndex: false,
                    breakPoints: [],
                };
                _this.containerData[$this.attr('id')] = containerData;
                window.setTimeout(function() {
                    cacheBreakpoints($this, _this);
                    updateBreakIndex($this, _this);
                }, 0);
            }
        });
    }

    /**
     * Update container break points, if event is not bubbling
     *
     * @param {event} e - Event
     */
    function onColumnRendered(e) {
        var _this = e.data.self;
        if (this !== e.target) {
            return;
        }
        $('#col1, #col2').each(function() {
            var $this = $(this);
            if ($this.find('.tabs').length) {
                window.setTimeout(function() {
                    cacheBreakpoints($this, _this);
                    updateBreakIndex($this, _this);
                }, 0);
            }
        });
    }

    /**
     * Update container break points
     *
     * @param {event} e - Event
     */
    function onWindowResized(e) {
        var _this = e.data.self;
        $('#col1, #col2').each(function() {
            var $this = $(this);
            if ($this.find('.tabs').length) {
                cacheBreakpoints($this, _this);
                updateBreakIndex($this, _this);
            }
        });
    }

    /**
     * Cache tab break points for container
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {object} e - The behavior
     */
    function cacheBreakpoints($container, e) {
        var containerData = {};
        var w = $container.find('.dropdown-nav-item').outerWidth(true)+1;
        containerData.breakPoints = [];
        $container.find('.tabs').not('.cloned').show().children('li').not('.dropdown-nav-item').each(function() {
            containerData.breakPoints.push(w += $(this).outerWidth(true) + 1);
        });
        e.containerData[$container.attr('id')] = containerData;
    }

    /**
     * Check break points and accordingly set the break index
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {object} e - The behavior
     */
    function updateBreakIndex($container, e) {
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
     * Set the break index and if value has changed render Tabs
     *
     * @param {jQuery} $container - Element containing the tabs
     *
     * @param {int} newIndex - The index to be set
     *
     * @param {object} e - The behavior
     */
    function setBreakIndex($container, newIndex, e) {
        var containerData = e.containerData[$container.attr('id')];
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

        this.on('rendered', '#layout', onInitialize, this);
        this.on('rendered', '#col1, #col2', onColumnRendered, this);
        this.on('close-column', '#col1, #col2', onColumnRendered, this);

        $(window).resize({self: this}, onWindowResized);
    };

    ResponsiveTabBar.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ResponsiveTabBar = ResponsiveTabBar;
})(Icinga, jQuery);
