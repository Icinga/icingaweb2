<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Web\Form;

/**
 * Form class for proxy
 */
class ProxyConfigForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_general_proxy');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'global_use_proxy',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'value'         => false,
                'label'         => $this->translate('Use proxy for Internet connections'),
                'description'   => $this->translate(
                    'Set whether to use a proxy for all Internet connections if the application is behind a proxy.'
                )
            )
        );

        if (isset($formData['global_use_proxy']) && $formData['global_use_proxy'] === "1") {
            $this->addElement(
                'text',
                'proxy_http',
                array(
                    'required'      => true,
                    'label'         => $this->translate('HTTP(S) Proxy'),
                    'description'   => $this->translate('The proxy which will be used for poxy connections.')
                )
            );
            $this->addElement(
                'checkbox',
                'proxy_http_request_fulluri',
                array(
                    'required'      => true,
                    'value'         => false,
                    'label'         => $this->translate('Request Full URI'),
                    'description'   => $this->translate(
                        'When checked, the entire URI will be used when constructing the request.'
                    )
                )
            );
        }

        return $this;
    }
}
