<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

use Icinga\Web\Form;

class DashletForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'pane',
            array(
                'required'      => true,
                'label'         => $this->translate('Pane'),
                'description'   => $this->translate('The name of the dashboard pane in which to display this dashlet')
            )
        );
        $this->addElement(
            'text',
            'url',
            array(
                'required'      => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate('The url to load in the dashlet')
            )
        );
    }
}
