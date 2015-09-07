<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

use Icinga\Web\Form;

class NavigationItemForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'url',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate(
                    'The url of this navigation item. Leave blank if you only want the name being displayed.'
                )
            )
        );

        $this->addElement(
            'text',
            'icon',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Icon'),
                'description'   => $this->translate(
                    'The icon of this navigation item. Leave blank if you do not want a icon being displayed.'
                )
            )
        );
    }
}
