<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Setup;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
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
            new Note(
                'description',
                array(
                    'value' => mt(
                        'monitoring',
                        'Please configure below how Icinga Web 2 should retrieve monitoring information.'
                    )
                )
            )
        );

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Backend Name'),
                'description'   => mt('monitoring', 'The identifier of this backend')
            )
        );

        $resourceTypes = array('livestatus' => 'Livestatus');
        if (Platform::extensionLoaded('mysql') || Platform::extensionLoaded('pgsql')) {
            $resourceTypes['ido'] = 'IDO';
        }

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
