(function(Icinga, $) {

    'use strict';

    var bodyOverflow;

    function Modal(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.$overlay = $('<div id="modal-overlay"><div id="modal-container"></div></div>');

        $('#layout').append('beforeend', this.$overlay[0]);

        this.on('click', '.modal-toggle', this.onModalToggleClick, this);
    }

    Modal.prototype = new Icinga.EventListener();

    Modal.prototype.onModalToggleClick = function(event) {
        var modal = event.data.self;

        modal.show();
    };

    Modal.prototype.onOverlayClick = function(event) {
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

    Modal.prototype.onKeydown = function(event) {
        var modal = event.data.self;

        if (event.keyCode === 27) {
            modal.hide();
        }
    };

    Modal.prototype.show = function() {
        bodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        $(document).on('keyup', { self: this }, this.onKeydown);
        this.$overlay.on('click', { self: this }, this.onOverlayClick);

        this.$overlay.addClass('active');
    };

    Modal.prototype.hide = function() {
        document.body.style.overflow = bodyOverflow;

        $('document').off('keydown', this.onKeydown);
        this.$overlay.off('click', this.onOverlayClick);

        this.$overlay.removeClass('active');
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Modal = Modal;

}) (Icinga, jQuery);
