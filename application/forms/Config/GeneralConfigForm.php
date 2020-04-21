<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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

    public function onRequest() {
        if ($this->config->getConfigObject()->current()->__get('config_backend') === 'ini') {
            $this->warning('Ini backend type is deprecated and will be removed with version 2.10');
        }
    }
}
