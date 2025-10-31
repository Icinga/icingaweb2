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
        this.on('click', '[data-icinga-modal][href]', this.onModalToggleClick, this);
        this.on('mousedown', '#layout > #modal', this.onModalLeave, this);
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
        var $redirectTarget = $a.closest('.container');

        _this.modalOpener = event.currentTarget;

        // Disable pointer events to block further function calls
        _this.modalOpener.style.pointerEvents = 'none';

        // Add showCompact, we don't want controls in a modal
        url = _this.icinga.utils.addUrlFlag(url, 'showCompact');

        // Set the toggle's container to use it as redirect target
        $modal.data('redirectTarget', $redirectTarget);

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
                _this.icinga.loader.renderContentToContainer(req.responseText, $redirectTarget, req.action);
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
    Modal.prototype.onFormSubmit = function(event) {
        const _this = event.data.self;
        const $form = $(event.currentTarget).closest('form');
        const $modal = $form.closest('#modal');

        let $button;
        if (typeof event.originalEvent !== 'undefined'
            && typeof event.originalEvent.submitter !== 'undefined'
            && event.originalEvent.submitter !== null) {
            $button = $(event.originalEvent.submitter);
        }

        // Safari fallback only
        const $rememberedSubmitButton = $form.data('submitButton');
        if (typeof $rememberedSubmitButton !== 'undefined') {
            if (typeof $button === 'undefined' && $form.has($rememberedSubmitButton)) {
                $button = $rememberedSubmitButton;
            }

            $form.removeData('submitButton');
        }

        let $autoSubmittedBy;
        if (event.detail !== null && typeof event.detail === 'object' && "submittedBy" in event.detail) {
            $autoSubmittedBy = $(event.detail.submittedBy);
        }

        // Prevent our other JS from running
        $modal[0].dataset.noIcingaAjax = '';

        const req = _this.icinga.loader.submitForm($form, $autoSubmittedBy, $button);
        req.addToHistory = false;
        req.done(function (data, textStatus, req) {
            const title = req.getResponseHeader('X-Icinga-Title');
            if (!! title) {
                _this.setTitle($modal, decodeURIComponent(title).replace(/\s::\s.*/, ''));
            }

            if (req.getResponseHeader('X-Icinga-Redirect')) {
                _this.hide($modal);
            }
        }).always(function () {
            delete $modal[0].dataset.noIcingaAjax;
        });

        if (! ('baseTarget' in $form[0].dataset)) {
            req.$redirectTarget = $modal.data('redirectTarget');
        }

        if (typeof $autoSubmittedBy === 'undefined') {
            // otherwise the form is submitted several times by clicking the "Submit" button several times
            $form.find('input[type=submit],button[type=submit],button:not([type])').prop('disabled', true);
        }

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
        let form = event.currentTarget.form;
        let modal = form.closest('#modal');

        // Prevent our other JS from running
        modal.dataset.noIcingaAjax = '';

        form.dispatchEvent(new CustomEvent('submit', {
            cancelable: true,
            bubbles: true,
            detail: { submittedBy: event.currentTarget }
        }));
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

        if (! event.isDefaultPrevented() && event.key === 'Escape') {
            let $modal = _this.$layout.children('#modal');
            if ($modal.length) {
                _this.hide($modal);
            }
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
        // Remove pointerEvent none style to make the button clickable again
        this.modalOpener.style.pointerEvents = '';
        this.modalOpener = null;

        $modal.removeClass('active');
        // Using `setTimeout` here to let the transition finish
        setTimeout(function () {
            $modal.find('#modal-content').trigger('close-modal');
            $modal.remove();
        }, 200);
    };

    Icinga.Behaviors.Modal = Modal;

})(Icinga, jQuery);
