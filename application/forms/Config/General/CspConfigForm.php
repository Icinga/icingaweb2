<?php

namespace Icinga\Forms\Config\General;

use Icinga\Application\Config;
use Icinga\Web\Session;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;

class CspConfigForm extends CompatForm
{
    use FormUid;
    use CsrfCounterMeasure;

    public function __construct()
    {
        $this->setAttribute("name", "csp_config");
        $this->applyDefaultElementDecorators();
    }

    protected function assemble(): void
    {
        $this->addElement($this->createUidElement());

        $this->addCsrfCounterMeasure(Session::getSession()->getId());

        $this->addElement(
            'checkbox',
            'use_strict_csp',
            [
                'label'         => $this->translate('Enable strict CSP'),
                'description'   => $this->translate(
                    'Set whether to use strict content security policy (CSP).'
                    . ' This setting helps to protect from cross-site scripting (XSS).'
                ),
            ],
        );

        $this->addElement('textarea', 'custom_csp', [
            'label' => 'Custom CSP',
            'description' => $this->translate(
                'Set custom CSP directives. These values are parsed and merged with the values supplied by modules'
                . ' and navigation items.'
            ),
        ]);

        $this->addElement('submit', 'submit', [
            'label' => t('Save changes'),
        ]);
    }

    protected function onSuccess(): void
    {
        $config = Config::app();

        $section = $config->getSection('security');
        $section['use_strict_csp'] = $this->getValue('use_strict_csp');
        $section['custom_csp'] = $this->getValue('custom_csp');
        $config->setSection('security', $section);

        $config->saveIni();
    }
}
