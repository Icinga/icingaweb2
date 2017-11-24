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
        $appConfigForm->setTitle('Application');
        $appConfigForm->setDecorators(array(array('Description', array('tag' => 'h2', 'class' => 'form-sub-header')), 'FormElements', 'Form'));
        $this->addSubForm($loggingConfigForm->create($formData));
        $loggingConfigForm->setTitle('Logging');
        $loggingConfigForm->setDecorators(array(array('Description', array('tag' => 'h2', 'class' => 'form-sub-header')), 'FormElements', 'Form'));
        $this->addSubForm($themingConfigForm->create($formData));
        $themingConfigForm->setTitle('Theming');
        $themingConfigForm->setDecorators(array(array('Description', array('tag' => 'h2', 'class' => 'form-sub-header')), 'FormElements', 'Form'));
        $this->addSubForm($domainConfigForm->create($formData));
        $domainConfigForm->setTitle('Domain');
        $domainConfigForm->setDecorators(array(array('Description', array('tag' => 'h2', 'class' => 'form-sub-header')), 'FormElements', 'Form'));
    }
}
