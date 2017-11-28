<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Config;
use Icinga\Web\Form;

/**
 * Configuration form for the session backend
 *
 * This form is not used directly but as subform to the {@link GeneralConfigForm}.
 */
class SessionBackendConfigForm extends Form
{
    public function init()
    {
        $this->setName('form_config_general_sessionbackend');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'sessionbackend_type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Backend Type'),
                'description'   => $this->translate('The type of backend'),
                'multiOptions'  => array(
                    'builtin'   => $this->translate('PHP\'s built-in backend'),
                    'redis'     => $this->translate('Redis'),
                ),
                'value'         => 'builtin'
            )
        );

        if (isset($formData['sessionbackend_type']) && $formData['sessionbackend_type'] === 'redis') {
            $resources = Config::app('resources')->getConfigObject()->setKeyColumn('name')->select()
                ->from(null, array('name'))
                ->where('type', 'redis')
                ->order('name')
                ->fetchColumn();

            $this->addElement(
                'select',
                'sessionbackend_resource',
                array(
                    'label'         => $this->translate('Resource'),
                    'description'   => $this->translate('The Redis resource'),
                    'required'      => true,
                    'multiOptions'  => array_combine($resources, $resources)
                )
            );
        }

        return $this;
    }
}
