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
        $appConfigForm->setTitle($this->translate('Application'));
        $appConfigForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        $this->addSubForm($loggingConfigForm->create($formData));
        $loggingConfigForm->setTitle($this->translate('Logging'));
        $loggingConfigForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        $this->addSubForm($themingConfigForm->create($formData));
        $themingConfigForm->setTitle($this->translate('Theming'));
        $themingConfigForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        $this->addSubForm($domainConfigForm->create($formData));
        $domainConfigForm->setTitle($this->translate('Domain'));
        $domainConfigForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );
    }
}
