// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

(function (Icinga, $) {

    'use strict';

    var $body;
    var $overlay;

    function Modal(icinga) {
        Icinga.EventListener.call(this, icinga);

        $body = $('body');
        $overlay = $('<div id="modal-overlay"><div id="modal-container"></div></div>');

        $overlay.appendTo($('#layout'));

        this.on('click', '.modal-toggle', this.onModalToggleClick, this);
    }

    Modal.prototype = new Icinga.EventListener();

    Modal.prototype.block = function () {
        $body.css('overflow', 'hidden');

        $overlay.addClass('active');
    };

    Modal.prototype.hide = function () {
        this.unblock();

        $('document').off('keydown.modal', this.onKeydown);

        $overlay.off('click.modal', this.onOverlayClick);
    };

    Modal.prototype.onKeydown = function (event) {
        var modal = event.data.self;

        if (event.which === 27) {
            modal.hide();
        }
    };

    Modal.prototype.onModalToggleClick = function (event) {
        var modal = event.data.self;

        modal.show();
    };

    Modal.prototype.onOverlayClick = function (event) {
        var modal = event.data.self;
        var $target = $(event.target);
        var $closeButton = $(event.target).closest('button.close');

        console.log($target);

        if ($target.is('#modal-overlay')) {
            modal.hide();
        } else if ($closeButton.length) {
            modal.hide();
        }
    };

    Modal.prototype.show = function () {
        $(document).on('keydown.modal', { self: this }, this.onKeydown);

        $overlay.on('click.modal', { self: this }, this.onOverlayClick);

        $overlay.addClass('active');
    };

    Modal.prototype.unblock = function () {
        $body.css('overflow', '');

        $overlay.removeClass('active');
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Modal = Modal;

}(Icinga, jQuery));
