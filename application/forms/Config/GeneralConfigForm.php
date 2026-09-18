<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config;

use Icinga\Forms\Config\General\ApplicationConfigForm;
use Icinga\Forms\Config\General\DefaultAuthenticationDomainConfigForm;
use Icinga\Forms\Config\General\LoggingConfigForm;
use Icinga\Forms\Config\General\ThemingConfigForm;
use Icinga\Forms\ConfigForm;

/**
 * Configuration form for application-wide options
 */
class GeneralConfigForm extends ConfigForm
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_general');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $appConfigForm = new ApplicationConfigForm();
        $loggingConfigForm = new LoggingConfigForm();
        $themingConfigForm = new ThemingConfigForm();
        $domainConfigForm = new DefaultAuthenticationDomainConfigForm();
        $this->addSubForm($appConfigForm->create($formData));
        $this->addSubForm($loggingConfigForm->create($formData));
        $this->addSubForm($themingConfigForm->create($formData));
        $this->addSubForm($domainConfigForm->create($formData));
    }
}
