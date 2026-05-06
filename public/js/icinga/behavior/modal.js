// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

;(function(Icinga, $) {

    'use strict';

    let functions = null;
    let iterator;
    let not$;

    try {
        functions = require('icinga/icinga-php-library/functions');
        iterator = require('icinga/icinga-php-library/iterator');
        not$ = require('icinga/icinga-php-library/notjQuery');
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

            this._modal = null;
            this.layout = document.getElementById('layout');
            this.ghost = document.getElementById('modal-ghost');
            this.hasChanges = false;
            this._wobbleTimeout = null;

            this.on('submit', '#modal form', this.onFormSubmit.bind(this));
            this.on('change', '#modal form select.autosubmit', this.onFormAutoSubmit.bind(this));
            this.on('change', '#modal form input.autosubmit', this.onFormAutoSubmit.bind(this));
            this.on('click', '[data-icinga-modal][href]', this.onModalToggleClick.bind(this));
            this.on('mousedown', '#layout > #modal', this.onModalLeave.bind(this));
            this.on('click', '.modal-header > button', this.onModalClose.bind(this));
            this.on('paste', '#modal form', this.onPaste.bind(this));
            this.on('change', '#modal form', this.onChange.bind(this));
            this.on('keydown', '#modal form', this.onKeyDown.bind(this));
            this.on('keydown', this.onEscapeKey.bind(this));
        }

        get modal() {
            if (this._modal === null) {
                this._modal = document.getElementById('modal');
            }

            return this._modal;
        }

        set modal(value) {
            if (value !== this._modal && this._modal !== null) {
                this._modal.remove();
            }

            this._modal = value;
        }

        /**
         * Event handler for toggling modals. Shows the link target in a modal dialog.
         *
         * @param event {Event} The `onClick` event triggered by the clicked modal-toggle element
         * @returns {boolean}
         */
        onModalToggleClick(event) {
            const a = event.currentTarget;
            let url = a.getAttribute('href');
            const modal = this.ghost.cloneNode(true);
            const redirectTarget = a.closest('.container');

            this.modalOpener = event.currentTarget;

            // Disable pointer events to block further function calls
            this.modalOpener.style.pointerEvents = 'none';

            // Add showCompact, we don't want controls in a modal
            url = this.icinga.utils.addUrlFlag(url, 'showCompact');

            if (redirectTarget !== null) {
                // Set the toggle's container to use it as redirect target
                modal.dataset.redirectTarget = this.icinga.utils.getCSSPath(redirectTarget);
            }

            // Final preparations, the id is required so that it's not `display:none` anymore
            modal.setAttribute('id', 'modal');
            this.layout.append(modal);

            const req = this.icinga.loader.loadUrl(url, $(modal.querySelector('#modal-content')));
            req.addToHistory = false;

            req.done(() => {
                this.setTitle(req.$target.data('icingaTitle').replace(/\s::\s.*/, ''));
                this.show();
                this.focus();
            });
            req.fail((req, _, errorThrown) => {
                if (req.status >= 500) {
                    // Yes, that's done twice (by us and by the base fail handler),
                    // but `renderContentToContainer` does too many useful things..
                    this.icinga.loader.renderContentToContainer(req.responseText, $(redirectTarget), req.action);
                } else if (req.status > 0) {
                    const msg = "".concat(...iterator.map(
                        not$.render("<div>" + req.responseText + "</div>").querySelectorAll('.error-message'),
                        (el) => el.innerText
                    ));
                    if (msg && msg !== errorThrown) {
                        errorThrown += ': ' + msg;
                    }

                    this.icinga.loader.createNotice('error', errorThrown);
                }

                this.hide();
            });

            return false;
        }

        /**
         * Event handler for form submits within a modal.
         *
         * @param event {Event} The `submit` event triggered by a form within the modal
         * @returns {boolean}
         */
        onFormSubmit(event) {
            const form = event.currentTarget.closest('form');

            let $button;
            if (typeof event.originalEvent !== 'undefined'
                && typeof event.originalEvent.submitter !== 'undefined'
                && event.originalEvent.submitter !== null) {
                $button = $(event.originalEvent.submitter);
            }

            // Safari fallback only
            const $rememberedSubmitButton = $(form).data('submitButton');
            if (typeof $rememberedSubmitButton !== 'undefined') {
                if (typeof $button === 'undefined' && $rememberedSubmitButton[0].closest('form') === form) {
                    $button = $rememberedSubmitButton;
                }

                $(form).removeData('submitButton');
            }

            let $autoSubmittedBy;
            if (event.detail !== null && typeof event.detail === 'object' && "submittedBy" in event.detail) {
                $autoSubmittedBy = $(event.detail.submittedBy);
            }

            // Prevent our other JS from running
            this.modal.dataset.noIcingaAjax = '';

            const req = this.icinga.loader.submitForm($(form), $autoSubmittedBy, $button);
            req.addToHistory = false;

            req.done((data, textStatus, req) => {
                const title = req.getResponseHeader('X-Icinga-Title');
                if (!! title) {
                    this.setTitle(decodeURIComponent(title).replace(/\s::\s.*/, ''));
                }

                if (req.getResponseHeader('X-Icinga-Redirect')) {
                    this.hide();
                }
            }).always(() => {
                delete this.modal?.dataset.noIcingaAjax;
            });

            if (! ('baseTarget' in form.dataset) && 'redirectTarget' in this.modal.dataset) {
                req.$redirectTarget = $(this.modal.dataset.redirectTarget);
            }

            if (typeof $autoSubmittedBy === 'undefined') {
                // otherwise the form is submitted several times by clicking the "Submit" button several times
                form.querySelectorAll('input[type=submit],button[type=submit],button:not([type])')
                    .forEach(function(button) {
                        button.setAttribute("disabled", "disabled");
                    });
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
            const form = event.currentTarget.form;

            // Prevent our other JS from running
            this.modal.dataset.noIcingaAjax = '';

            not$(form).trigger('submit', { submittedBy: event.currentTarget });
        };

        /**
         * Event handler for closing the modal. Closes it when the user clicks on the overlay.
         *
         * @param event {Event} The `click` event triggered by clicking on the overlay
         */
        onModalLeave(event) {
            if (event.target === this.modal) {
                if (this.hasChanges) {
                    this.wobble();
                } else {
                    this.hide();
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

            if (this.hasChanges) {
                this.wobble();
            } else if (! event.isDefaultPrevented()) {
                this.hide();
            }
        }

        /**
         * Event handler for closing the modal. Closes it when the user clicks on the close button.
         *
         * @param event {Event} The `click` event triggered by clicking on the close button
         */
        onModalClose(event) {
            this.hide();
        }

        /**
         * Event handler for pasting into the modal form. Sets the hasChanges flag to true.
         *
         * @param event The `paste` event triggered by pasting into the form
         */
        onPaste(event) {
            /** @type {ClipboardEvent} */
            const originalEvent = event.originalEvent;
            if (originalEvent.clipboardData.types.length) {
                // Only set hasChanges flag if clipboard data is present
                this.hasChanges = true;
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
            if (! functions?.isSpecialKeyPress(event)) {
                this.hasChanges = true;
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
            this.hasChanges = true;
        }

        /**
         * Make final preparations and add the modal to the DOM
         */
        show() {
            this.modal.classList.add("active");
        }

        /**
         * Set a title for the modal
         *
         * @param title {string} The title
         */
        setTitle(title) {
            this.modal.querySelector('.modal-header > h1').textContent = title;
        }

        /**
         * Focus the modal
         */
        focus() {
            this.icinga.ui.focusElement($(this.modal.querySelector('.modal-window')));
        }

        /**
         * Wobble the modal
         */
        wobble() {
            let timingOffset = 0;
            if (this._wobbleTimeout !== null) {
                clearTimeout(this._wobbleTimeout);
                // Do not interrupt the animation by removing the class too early.
                // This is done by identifying the running animation and synchronizing the timeout with it.
                for (const animation of this.modal.getAnimations({subtree: true})) {
                    if (animation.effect?.target?.matches('.modal-window')) {
                        timingOffset = animation.currentTime;

                        break;
                    }
                }
            } else {
                this.modal.classList.add("wobble");
            }

            this._wobbleTimeout = setTimeout(() => {
                this.modal?.classList.remove("wobble");
                this._wobbleTimeout = null;
            }, 1000 - timingOffset);
        }

        /**
         * Hide the modal and remove it from the DOM
         */
        hide() {
            if (this.modal === null) {
                return;
            }

            // Remove pointerEvent none style to make the button clickable again
            this.modalOpener.style.pointerEvents = '';
            this.modalOpener = null;
            this.hasChanges = false;

            this.modal.classList.remove("active");

            // Using `setTimeout` here to let the transition finish
            setTimeout(() => {
                not$(this.modal.querySelector('#modal-content'))
                    .trigger('close-modal')
                    .then(() => this.modal = null);
            }, 200);
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Modal = Modal;

})(Icinga, jQuery);
