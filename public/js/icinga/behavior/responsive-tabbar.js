/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    function onRendered(e) {
	    console.log(e.type);
        var _this = e.data.self;
		if ($('#layout').hasClass('twocols')) {
			$('#col1, #col2').each( function() {
				var $this = $(this);
			    if ($this.find('.tabs')) {
		            cacheBreakpoints($this, _this);
		            updateBreakIndex($this, _this);
		        }
			});
	    } else {
		    var $this = $(this);
		    if ($this.find('.tabs')) {
	            cacheBreakpoints($this, _this);
	            updateBreakIndex($this, _this);
	        }
	    }
    }

    function onWindowResized(e) {
	    console.log(e.type);
	    var _this = e.data.self;
		$('#col1, #col2').each(function() {
			var $this = $(this);

			if (_this.containerData[$this.attr("id")]) {
				if ($this.find('.tabs')) {
		            updateBreakIndex($this, _this);
		        }
	        }
		});
    }

    function onLayoutchange(e) {
	    console.log(e.type);
	    var _this = e.data.self;
		$('#col1, #col2').each(function() {
			var $this = $(this);
			if ($this.find('.tabs')) {
				cacheBreakpoints($this, _this);
	            updateBreakIndex($this, _this);
	        }
		});
    }

    function cacheBreakpoints($container, e) {
        var containerData = {};
        containerData.breakPoints = [];
        var w = $container.find('.dropdown-nav-item').outerWidth(true)+1;
        $container.find(".tabs").not(".cloned").show();
        $container.find(".tabs").not(".cloned").children("li").not('.dropdown-nav-item').each(function() {
            containerData.breakPoints.push(w += $(this).outerWidth(true) + 1);
        });
        e.containerData[$container.attr('id')] = containerData;
//         console.log("cacheBreak", e.containerData);
    }

    function updateBreakIndex($container, e) {

        var b = false;

		// show container before calculating dimensions
        var $tabContainer = $container.find('.tabs').show();

        var breakPoints = e.containerData[$container.attr('id')].breakPoints;
		var dw = $tabContainer.find('.dropdown-nav-item').outerWidth(true) + 1;
		var tw = $tabContainer.width();
// 		var w = tw - dw;
		var w = $tabContainer.width();

        for (var i = 0; i < breakPoints.length; i++) {
            if ( breakPoints[i] > w) {
                b = i;
                break;
            }
        }

//         console.log("updateBreak", e.containerData);
        setBreakIndex($container, b, e);
    }

	/**
     * Set the breakIndex and if value has changed render Tabs
     *
     * @param {jQuery}		$container	Element containing the tabs
     *
     * @param {Int}			NewIndex 	New Value for the breakIndex
     */
    function setBreakIndex($container, newIndex, e) {
//         console.log("setBreak", e.containerData);
		var containerData = e.containerData[$container.attr("id")];
		console.log("event", e);
		console.log("container", $container);
		console.log("data", containerData);
		console.log("new : old", newIndex, containerData.breakIndex)

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
     * @param {jQuery} $container	Element containing the tabs
     */
    function renderTabs($container, e) {
        var breakIndex = e.containerData[$container.attr('id')].breakIndex;

        $container.find('.tabs.cloned').remove();
        if (breakIndex) {
            var $tabsClone = $container.find('.tabs').not('.cloned').hide().clone().addClass("cloned");

            // if not exists, create dropdown
            var $dropdown = null;
            if ( $tabsClone.children(".dropdown-nav-item").length > 0 ) {
                $dropdown = $tabsClone.children(".dropdown-nav-item");
            } else {
                $dropdown = $('<li class="dropdown-nav-item"><a href="#" class="dropdown-toggle" title="Dropdown menu" aria-label="Dropdown menu"><i aria-hidden="true" class="icon-down-open"></i></a><ul class="nav"></ul></li>');
                $tabsClone.append($dropdown);
            } // END if not exists, create dropdown

            // insert tab items into dropdown
            var l = $tabsClone.children("li").not('.dropdown-nav-item').length;
            for (var i = breakIndex; i < l; i++) {
                var $tab = $($tabsClone.children("li").not('.dropdown-nav-item').get(i));

                $dropdown.children('ul').append($tab.clone());
                $tab.hide();
            } // END insert tab items into dropdown

			$container.find('.tabs').not('.cloned').hide();
            $container.find(".controls").prepend($tabsClone.show());
        } else {
            //breakIndex false: No need for cloned tabs
            $container.find('.tabs').not('.cloned').show();
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for managing tab bar width
     *
     * The ResponsiveTabBar will wrap tabs in a dropdown if the containing
     * tab bar becomes insufficient
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var ResponsiveTabBar = function(icinga) {
	    this.containerData = {};
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', '#col1, #col2', onRendered, this);


		$(window).resize({self: this}, onWindowResized);

		this.on('layout-change', onLayoutchange, this);
/*
		this.on('close-column', '#col1, #col2', onRendered, this);
*/
    };

    ResponsiveTabBar.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ResponsiveTabBar = ResponsiveTabBar;
})(Icinga, jQuery);
