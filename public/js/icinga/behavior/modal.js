// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

;(function(Icinga, $) {

    'use strict';

    let functions = null;

    try {
        functions = require('icinga/icinga-php-library/functions');
    } catch (error) {
        console.error('Failed to require library:', error);
    }

    /**
     * Behavior for modal dialogs.
     *
     * @param icinga {Icinga} The current Icinga Object
     */
    class Modal extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.$layout = $('#layout');
            this.$ghost = $('#modal-ghost');
            this.hasChanges = false;
            this._wobbleTimeout = null;

            this.on('submit', '#modal form', this.onFormSubmit, this);
            this.on('change', '#modal form select.autosubmit', this.onFormAutoSubmit, this);
            this.on('change', '#modal form input.autosubmit', this.onFormAutoSubmit, this);
            this.on('click', '[data-icinga-modal][href]', this.onModalToggleClick, this);
            this.on('mousedown', '#layout > #modal', this.onModalLeave, this);
            this.on('click', '.modal-header > button', this.onModalClose, this);
            this.on('paste', '#modal form', this.onPaste, this);
            this.on('change', '#modal form', this.onChange, this);
            this.on('keydown', '#modal form', this.onKeyDown, this);
            this.on('keydown', this.onEscapeKey, this);
        }

        /**
         * Event handler for toggling modals. Shows the link target in a modal dialog.
         *
         * @param event {Event} The `onClick` event triggered by the clicked modal-toggle element
         * @returns {boolean}
         */
        onModalToggleClick(event) {
            const _this = event.data.self;
            const $a = $(event.currentTarget);
            let url = $a.attr('href');
            const $modal = _this.$ghost.clone();
            const $redirectTarget = $a.closest('.container');

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

            const req = _this.icinga.loader.loadUrl(url, $modal.find('#modal-content'));
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
                    const msg = $(req.responseText).find('.error-message').text();
                    if (msg && msg !== errorThrown) {
                        errorThrown += ': ' + msg;
                    }

                    _this.icinga.loader.createNotice('error', errorThrown);
                }

                _this.hide($modal);
            });

            return false;
        }

        /**
         * Event handler for form submits within a modal.
         *
         * @param event {Event} The `submit` event triggered by a form within the modal
         * @param $autoSubmittedBy {jQuery} The element triggering the auto submit, if any
         * @returns {boolean}
         */
        onFormSubmit(event) {
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
        }

        /**
         * Event handler for form auto submits within a modal.
         *
         * @param event {Event} The `change` event triggered by a form input within the modal
         * @returns {boolean}
         */
        onFormAutoSubmit(event) {
            let form = event.currentTarget.form;
            let modal = form.closest('#modal');

            // Prevent our other JS from running
            modal.dataset.noIcingaAjax = '';

            form.dispatchEvent(new CustomEvent('submit', {
                cancelable: true,
                bubbles: true,
                detail: {submittedBy: event.currentTarget}
            }));
        };

        /**
         * Event handler for closing the modal. Closes it when the user clicks on the overlay.
         *
         * @param event {Event} The `click` event triggered by clicking on the overlay
         */
        onModalLeave(event) {
            const _this = event.data.self;
            const $target = $(event.target);

            if ($target.is('#modal')) {
                if (_this.hasChanges) {
                    _this.wobble($target);
                } else {
                    _this.hide($target);
                }
            }
        }

        /**
         * Event handler for closing the modal. Closes it when the user pushes ESC.
         *
         * @param event {KeyboardEvent} The `keydown` event triggered by pushing a key
         */
        onEscapeKey(event) {
            if (event.key !== 'Escape') {
                return;
            }

            const _this = event.data.self;
            const $modal = _this.$layout.children('#modal');
            if (! $modal.length) {
                return;
            }

            if (_this.hasChanges) {
                _this.wobble($modal);
            } else if (! event.isDefaultPrevented()) {
                _this.hide($modal);
            }
        }

        /**
         * Event handler for closing the modal. Closes it when the user clicks on the close button.
         *
         * @param event {Event} The `click` event triggered by clicking on the close button
         */
        onModalClose(event) {
            const _this = event.data.self;

            _this.hide($(event.currentTarget).closest('#modal'));
        }

        /**
         * Event handler for pasting into the modal form. Sets the hasChanges flag to true.
         *
         * @param event The `paste` event triggered by pasting into the form
         */
        onPaste(event) {
            const _this = event.data.self;

            /** @type {ClipboardEvent} */
            const originalEvent = event.originalEvent;
            if (originalEvent.clipboardData.types.length) {
                // Only set hasChanges flag if clipboard data is present
                _this.hasChanges = true;
            }
        }

        /**
         * Event handler for input into the modal form. Sets the hasChanges flag to true.
         *
         * This is needed to detect changes in the form, as the `change` event is not always reliable.
         * Unless a text input or textarea is blurred, the `change` event might not be triggered.
         * Pushing Escape in this case would still close the modal without this.
         *
         * @param event {KeyboardEvent} The `keydown` event triggered by pushing a key
         */
        onKeyDown(event) {
            const _this = event.data.self;

            if (! functions?.isSpecialKeyPress(event)) {
                _this.hasChanges = true;
            }
        }

        /**
         * Event handler to register whether the modal form has been changed.
         *
         * In addition to `onKeyDown`, this is needed because checkboxes or select elements
         * do only trigger the `change` event, but at least rather reliably.
         *
         * @param event {Event} The change event
         */
        onChange(event) {
            const _this = event.data.self;
            _this.hasChanges = true;
        }

        /**
         * Make final preparations and add the modal to the DOM
         *
         * @param $modal {jQuery} The modal element
         */
        show($modal) {
            $modal.addClass('active');
        }

        /**
         * Set a title for the modal
         *
         * @param $modal {jQuery} The modal element
         * @param title {string} The title
         */
        setTitle($modal, title) {
            $modal.find('.modal-header > h1').html(title);
        }

        /**
         * Focus the modal
         *
         * @param $modal {jQuery} The modal element
         */
        focus($modal) {
            this.icinga.ui.focusElement($modal.find('.modal-window'));
        }

        /**
         * Wobble the modal
         *
         * @param $modal {jQuery} The modal element
         */
        wobble($modal) {
            const modal = $modal[0];
            let timingOffset = 0;
            if (this._wobbleTimeout !== null) {
                clearTimeout(this._wobbleTimeout);
                // Do not interrupt the animation by removing the class too early.
                // This is done by identifying the running animation and synchronizing the timeout with it.
                for (const animation of modal.getAnimations({subtree: true})) {
                    if (animation.effect?.target?.matches('.modal-window')) {
                        timingOffset = animation.currentTime;

                        break;
                    }
                }
            } else {
                modal.classList.add("wobble");
            }

            const _this = this;
            this._wobbleTimeout = setTimeout(function () {
                modal.classList.remove("wobble");
                _this._wobbleTimeout = null;
            }, 1000 - timingOffset);
        }

        /**
         * Hide the modal and remove it from the DOM
         *
         * @param $modal {jQuery} The modal element
         */
        hide($modal) {
            // Remove pointerEvent none style to make the button clickable again
            this.modalOpener.style.pointerEvents = '';
            this.modalOpener = null;
            this.hasChanges = false;

            $modal.removeClass('active');
            // Using `setTimeout` here to let the transition finish
            setTimeout(function () {
                $modal.find('#modal-content').trigger('close-modal');
                $modal.remove();
            }, 200);
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Modal = Modal;

})(Icinga, jQuery);
