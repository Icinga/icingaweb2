<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

class DashletForm extends NavigationItemForm
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
                'description'   => $this->translate(
                    'The url to load in the dashlet. For external urls, make sure to prepend'
                    . ' an appropriate protocol identifier (e.g. http://example.tld)'
                )
            )
        );
    }
}
