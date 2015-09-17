<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Navigation;

use Icinga\Forms\Navigation\NavigationItemForm;

class ServiceActionForm extends NavigationItemForm
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        parent::createElements($formData);

        $this->addElement(
            'text',
            'filter',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Filter'),
                'description'   => $this->translate(
                    'Display this action only for services matching this filter. Leave'
                    . ' blank if you want this action being displayed for all services'
                )
            )
        );
    }
}
