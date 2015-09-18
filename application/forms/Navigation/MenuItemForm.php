<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

class MenuItemForm extends NavigationItemForm
{
    /**
     * {@inheritdoc}
     */
    protected $requiresParentSelection = true;

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        parent::createElements($formData);
        $parentElement = $this->getParent()->getElement('parent');
        if ($parentElement !== null) {
            $parentElement->setDescription($this->translate(
                'The parent menu to assign this menu entry to. Select "None" to make this a main menu entry'
            ));
        }
    }
}
