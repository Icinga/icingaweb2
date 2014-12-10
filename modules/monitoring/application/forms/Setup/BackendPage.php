<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Application\Platform;

class BackendPage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_backend');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('monitoring', 'Monitoring Backend', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => mt(
                    'monitoring',
                    'Please configure below how Icinga Web 2 should retrieve monitoring information.'
                )
            )
        );

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'value'         => 'icinga',
                'label'         => mt('monitoring', 'Backend Name'),
                'description'   => mt('monitoring', 'The identifier of this backend')
            )
        );

        $resourceTypes = array();
        if (Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $resourceTypes['ido'] = 'IDO';
        }
        $resourceTypes['livestatus'] = 'Livestatus';

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Backend Type'),
                'description'   => mt('monitoring', 'The data source used for retrieving monitoring information'),
                'multiOptions'  => $resourceTypes
            )
        );
    }
}
