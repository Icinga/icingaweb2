/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';



    function onRendered(e) {
	    var _this = e.data.self;
        var $this = $(this);
        console.log("on rendered", e.type);
        if ($this.find(".tabs")) {
            cacheTabWidths($this, _this);
            updateBreakIndex($this, _this);
        }
    }

    function onWindowResized (e) {
	    console.log("window resize", e);
	    var _this = e.data.self;

        $('#col1, #col2').each(function(i) {
            var $this = $(this);
            if ($this.find(".tabs")) {
	            if (_this.containerData[$this.attr("id")]) {
		            console.log("update");
		            updateBreakIndex($this, _this);
	            }
            }
        });
    }

    function onLayoutChange (e) {

	    var _this = e.data.self;

		$('#col1, #col2').each(function(i) {
            var $this = $(this);
            if ($this.find(".tabs")) {
	            cacheTabWidths($this, _this);
	            updateBreakIndex($this, _this);
            }
        });
    }

	/**
     * Calculate the breakIndex according to tabs container width
     *
     * @param {jQuery}		$container	Element containing the tabs
     *
     * @param {object} 		e 			Event
     */
    function updateBreakIndex($container, e) {

        var breakIndex = false;

        var w = 0;
        var tabsElWidth = $container.find('.tabs').not(".cloned").width() - parseFloat($container.find('.tabs').not(".cloned").css("padding-left"));
        var tabWidths = e.containerData[$container.attr("id")].tabWidths;

        for (var i = 0; i < tabWidths.length; i++) {
            w += tabWidths[i];
            if (w > Math.floor(tabsElWidth)) {
                breakIndex = i;
            } else {
	            breakIndex = false;
            }
        }

        console.log("w : tabsW", w, tabsElWidth, $container, tabWidths);

        setBreakIndex($container, breakIndex, e);
    }


	/**
     * Set the breakIndex and if value has changed render Tabs
     *
     * @param {jQuery}		$container	Element containing the tabs
     *
     * @param {Int}			NewIndex 	New Value for the breakIndex
     */
    function setBreakIndex($container, newIndex, e) {
        if (newIndex == e.containerData[$container.attr("id")].breakIndex) {
            return;
        } else {
            $container.breakIndex = newIndex;
            renderTabs($container);
            console.log("break index change", $container, $container.breakIndex);
        }
    }

	/**
     * Save horizontal dimensions of the tabs once
     *
     * @param {jQuery}		$container	Element containing the tabs
     *
     * @param {object} 		e 			Event
     */
    function cacheTabWidths($container, e) {
        var containerData = {};
        containerData.tabWidths = [];

        $container.find(".tabs").not(".cloned").children("li").each(function () {
            containerData.tabWidths.push($(this).width() + parseFloat($(this).css("margin-right")));
        });

        e.containerData[$container.attr("id")] = containerData;

        console.log("tab widths cached", $container, containerData, e.containerData);
    }

    /**
     * Render Tabs of a container according to the updated breakIndex
     *
     * @param {jQuery} $container	Element containing the tabs
     */
    function renderTabs($container) {
        $container.find('.tabs.cloned').remove();
		var $tabs = $container.find(".tabs").show();
        if ($container.breakIndex) {
	    	var $additionalTabsDropdown = $('<li class="dropdown-nav-item additional-items"><a href="#" class="dropdown-toggle"><i class="icon-ellipsis"></i></a><ul class="nav"></ul></li>');
        	var $clonedTabs = $tabs.clone().addClass("cloned");
        	var $tabItems = $clonedTabs.children('li');

        	for (var i = $container.breakIndex-1; i < $tabItems.length + 1; i++) {
				var $item = $($tabItems.get(i));

				if ($item.children('ul.nav').length > 0) {
					$item.children('ul.nav').children('li').each(function(j) {
						$additionalTabsDropdown.children("ul").append(this);
					});
					$item.remove();
				} else {
					$additionalTabsDropdown.children("ul").append($item);
					$item.children("a").append($item.children("a").attr("title"));
				}
        	}
			$clonedTabs.append($additionalTabsDropdown);
        	$container.find(".controls").prepend($clonedTabs);

            $tabs.hide();
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
	    this.containerData = [];
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', '#col1, #col2', onRendered, this);
		$(window).resize({self: this}, onWindowResized);
		this.on('layout-change', '#col1, #col2', onLayoutChange, this);
		this.on('close-column', '#col1, #col2', onRendered, this);
    };

    ResponsiveTabBar.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ResponsiveTabBar = ResponsiveTabBar;
})(Icinga, jQuery);
