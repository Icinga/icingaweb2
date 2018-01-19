<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use Icinga\Forms\ConfigForm;
use Icinga\Web\Form\Validator\PcreListValidator;

/**
 * Configuration form for the /iframe/ action
 */
class IFrameConfigForm extends ConfigForm
{
    public function init()
    {
        $this->setName('form_config_iframe')
            ->setTitle($this->translate('IFrame URLs'))
            ->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $whitelist = isset($formData['iframe_whitelist']) && $formData['iframe_whitelist'];
        $this->addElements(array(
            array(
                'checkbox',
                'iframe_whitelist',
                array(
                    'autosubmit'    => true,
                    'label'         => $this->translate('Whitelist'),
                    'description'   => $this->translate('Whether the regex list below is a whitelist')
                )
            ),
            array(
                'textarea',
                'iframe_regexes',
                array(
                    'label'         => $whitelist ? $this->translate('Whitelist') : $this->translate('Blacklist'),
                    'description'   => $whitelist
                        ? $this->translate('List of allowed URLs (PCRE)')
                        : $this->translate('List of forbidden URLs (PCRE)'),
                    'validators'    => array(new PcreListValidator())
                )
            )
        ));
    }
}
