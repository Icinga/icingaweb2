<?php

namespace Icinga\Forms\Config\General;

use Icinga\Application\Config;
use Icinga\Util\Csp;
use Icinga\Web\Session;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;

class CspConfigForm extends CompatForm
{
    use FormUid;
    use CsrfCounterMeasure;

    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
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

        $this->addElement(
            'checkbox',
            'use_custom_csp',
            [
                'label'         => $this->translate('Enable Custom CSP'),
                'description'   => $this->translate(
                    'Specify whether to use a custom, user provided, string as the CSP-Header.'
                    . ' If you decide to provide your own CSP-Header, you are entirely responsible for keeping it'
                    . ' up-to-date.'
                ),
                'class' => 'autosubmit',
            ]
        );

        $this->addElement('hidden', 'hidden_custom_csp');

        $useCustomCsp = $this->getPopulatedValue('use_custom_csp', 'n') === 'y';
        $this->addElement('textarea', 'custom_csp', [
            'label' => 'Custom CSP',
            'description' => $this->translate(
                'Set a custom CSP-Header. This completely overrides the automatically generated one.'
            ),
            'disabled' => ! $useCustomCsp,
        ]);

        $customCspElement = $this->getElement('custom_csp');
        if ($useCustomCsp) {
            $value = $this->getPopulatedValue('hidden_custom_csp');
            if (! empty($value)) {
                $customCspElement->setValue($value);
            } else {
                $customCspElement->setValue($this->config->get('security', 'custom_csp'));
            }
        } else {
            $this->getElement('hidden_custom_csp')->setValue($this->getValue('custom_csp'));
            $customCspElement->setValue(Csp::getAutomaticContentSecurityPolicy());
        }

        $this->addElement('submit', 'submit', [
            'label' => t('Save changes'),
        ]);
    }

    protected function onSuccess(): void
    {
        $config = Config::app();

        $section = $config->getSection('security');
        $section['use_strict_csp'] = $this->getValue('use_strict_csp');
        $section['use_custom_csp'] = $this->getValue('use_custom_csp');
        $section['custom_csp'] = $this->getValue('custom_csp');
        $config->setSection('security', $section);

        $config->saveIni();
    }
}
