<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use Icinga\Web\Form;
use Zend_Form_Element;

trait ResourceConfigFormTrait
{
    /**
     * Create form element for the unique name of this resource
     *
     * @return Zend_Form_Element
     */
    protected function createNameElement()
    {
        /** @var Form $this */

        return $this->createElement(
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
