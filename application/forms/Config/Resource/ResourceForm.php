<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Web\Form;

/**
 * Form class for adding/modifying resources
 */
abstract class ResourceForm extends Form
{
    public function init()
    {
        $this->setName('form_config_resource');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
    }
}
