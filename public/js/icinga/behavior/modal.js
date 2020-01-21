/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for modal dialogs.
     *
     * @param icinga {Icinga} The current Icinga Object
     */
    var Modal = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;
        this.$layout = $('#layout');
        this.$ghost = $('#modal-ghost');

        this.on('submit', '#modal form', this.onFormSubmit, this);
        this.on('change', '#modal form select.autosubmit', this.onFormAutoSubmit, this);
        this.on('change', '#modal form input.autosubmit', this.onFormAutoSubmit, this);
        this.on('click', '[data-icinga-modal]', this.onModalToggleClick, this);
        this.on('click', '#layout > #modal', this.onModalLeave, this);
        this.on('click', '.modal-header > button', this.onModalClose, this);
        this.on('keydown', this.onKeyDown, this);
    };

    Modal.prototype = new Icinga.EventListener();

    /**
     * Event handler for toggling modals. Shows the link target in a modal dialog.
     *
     * @param event {Event} The `onClick` event triggered by the clicked modal-toggle element
     * @returns {boolean}
     */
    Modal.prototype.onModalToggleClick = function(event) {
        var _this = event.data.self;
        var $a = $(event.currentTarget);
        var url = $a.attr('href');
        var $modal = _this.$ghost.clone();
        var $urlTarget = _this.icinga.loader.getLinkTargetFor($a);

        // Add view=compact, we don't want controls in a modal
        url = _this.icinga.utils.addUrlParams(url, { 'view': 'compact' });

        // Set the toggle's base target on the modal to use it as redirect target
        $modal.data('redirectTarget', $urlTarget);

        // Final preparations, the id is required so that it's not `display:none` anymore
        $modal.attr('id', 'modal');
        _this.$layout.append($modal);

        var req = _this.icinga.loader.loadUrl(url, $modal.find('#modal-content'));
        req.addToHistory = false;
        req.done(function () {
            _this.setTitle($modal, req.$target.data('icingaTitle').replace(/\s::\s.*/, ''));
            _this.show($modal);
            _this.focus($modal);
        });
        req.fail(function (req, _, errorThrown) {
            if (req.status >= 500) {
                // Yes, that's done twice (by us and by the base fail handler),
                // but `renderContentToContainer` does too many useful things..
                _this.icinga.loader.renderContentToContainer(req.responseText, $urlTarget, req.action);
            } else if (req.status > 0) {
                var msg = $(req.responseText).find('.error-message').text();
                if (msg && msg !== errorThrown) {
                    errorThrown += ': ' + msg;
                }

                _this.icinga.loader.createNotice('error', errorThrown);
            }

            _this.hide($modal);
        });

        return false;
    };

    /**
     * Event handler for form submits within a modal.
     *
     * @param event {Event} The `submit` event triggered by a form within the modal
     * @param $autoSubmittedBy {jQuery} The element triggering the auto submit, if any
     * @returns {boolean}
     */
    Modal.prototype.onFormSubmit = function(event, $autoSubmittedBy) {
        var _this = event.data.self;
        var $form = $(event.currentTarget).closest('form');
        var $modal = $form.closest('#modal');

        var req = _this.icinga.loader.submitForm($form, $autoSubmittedBy);
        req.addToHistory = false;
        req.$redirectTarget = $modal.data('redirectTarget');
        req.done(function (data, textStatus, req) {
            if (req.getResponseHeader('X-Icinga-Redirect')) {
                _this.hide($modal);
            }
        });

        event.stopPropagation();
        event.preventDefault();
        return false;
    };

    /**
     * Event handler for form auto submits within a modal.
     *
     * @param event {Event} The `change` event triggered by a form input within the modal
     * @returns {boolean}
     */
    Modal.prototype.onFormAutoSubmit = function(event) {
        return event.data.self.onFormSubmit(event, $(event.currentTarget));
    };

    /**
     * Event handler for closing the modal. Closes it when the user clicks on the overlay.
     *
     * @param event {Event} The `click` event triggered by clicking on the overlay
     */
    Modal.prototype.onModalLeave = function(event) {
        var _this = event.data.self;
        var $target = $(event.target);

        if ($target.is('#modal')) {
            _this.hide($target);
        }
    };

    /**
     * Event handler for closing the modal. Closes it when the user clicks on the close button.
     *
     * @param event {Event} The `click` event triggered by clicking on the close button
     */
    Modal.prototype.onModalClose = function(event) {
        var _this = event.data.self;

        _this.hide($(event.currentTarget).closest('#modal'));
    };

    /**
     * Event handler for closing the modal. Closes it when the user pushed ESC.
     *
     * @param event {Event} The `keydown` event triggered by pushing a key
     */
    Modal.prototype.onKeyDown = function(event) {
        var _this = event.data.self;

        if (event.which === 27) {
            _this.hide(_this.$layout.children('#modal'));
        }
    };

    /**
     * Make final preparations and add the modal to the DOM
     *
     * @param $modal {jQuery} The modal element
     */
    Modal.prototype.show = function($modal) {
        $modal.addClass('active');
    };

    /**
     * Set a title for the modal
     *
     * @param $modal {jQuery} The modal element
     * @param title {string} The title
     */
    Modal.prototype.setTitle = function($modal, title) {
        $modal.find('.modal-header > h1').html(title);
    };

    /**
     * Focus the modal
     *
     * @param $modal {jQuery} The modal element
     */
    Modal.prototype.focus = function($modal) {
        this.icinga.ui.focusElement($modal.find('.modal-window'));
    };

    /**
     * Hide the modal and remove it from the DOM
     *
     * @param $modal {jQuery} The modal element
     */
    Modal.prototype.hide = function($modal) {
        $modal.removeClass('active');
        // Using `setTimeout` here to let the transition finish
        setTimeout(function () {
            $modal.remove();
        }, 200);
    };

    Icinga.Behaviors.Modal = Modal;

})(Icinga, jQuery);
