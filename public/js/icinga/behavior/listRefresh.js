/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.ListRefresh
 *
 * A toggleable flyover
 */
(function(Icinga, $) {

    'use strict';

    ListRefresh.touchDown = false;
    ListRefresh.startCoord;

    function ListRefresh(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('mousedown', '.item-list', this.onTouchDown, this);
        this.on('mousemove', '.item-list', this.onMouseMove, this);
        this.on('mouseup', '.item-list', this.onMouseUp, this);

        this.on('click', '.item-list a', function(e) { if (this.touchDown) { e.preventDefault() } }, this);
    }

    ListRefresh.prototype = new Icinga.EventListener();

    ListRefresh.prototype.onTouchDown = function(e) {
        e.preventDefault();
        console.log(e);
        console.log($(e.target).closest('.item-list'));

        let oEvent = e.originalEvent;

        let startX = oEvent.clientX;
        let startY = oEvent.clientY;

        this.startCoord = { x: startX, y: startY };
        this.touchDown = true;
        $(e.target).closest('.item-list li').css('pointer-events', 'none');
    };

    ListRefresh.prototype.onMouseMove = function(e) {
        let $target = $(e.target).closest('.item-list');
        if (this.touchDown) {
            let offsetY = e.clientY - this.startCoord.y;
            let resist = offsetY * offsetY *.3;
            $target.css('transform', 'translate3d(0,' + offsetY + 'px,0)');
        }
    };

    ListRefresh.prototype.onMouseUp = function(e) {
        e.preventDefault();
        console.log('stop');

        this.touchDown = false;
        $(e.target).closest('.item-list li').css('pointer-events', '');
        $(e.target).closest('.item-list').css('transform', 'translate3d(0,0,0)');
        // animate back
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.ListRefresh = ListRefresh;

})(Icinga, jQuery);
