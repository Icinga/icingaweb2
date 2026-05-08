<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    const DEFAULT_CLASSES = 'icinga-controls';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_confirm_removal');
        $this->getSubmitLabel() ?: $this->setSubmitLabel($this->translate('Confirm Removal'));
    }
}
