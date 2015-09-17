<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

class MenuItemForm extends NavigationItemForm
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $availableParents = $this->getParent()->listAvailableParents('menu-item');
        $this->addElement(
            'select',
            'parent',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Parent'),
                'description'   => $this->translate(
                    'The parent menu to assign this menu entry to. Select "None" to make this a main menu entry'
                ),
                'multiOptions'  => array_merge(
                    array('' => $this->translate('None', 'No parent for a navigation item')),
                    empty($availableParents) ? array() : array_combine($availableParents, $availableParents)
                )
            )
        );

        parent::createElements($formData);
    }
}
