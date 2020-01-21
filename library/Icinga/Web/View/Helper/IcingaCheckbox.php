<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\View\Helper;

class IcingaCheckbox extends \Zend_View_Helper_FormCheckbox
{
    public function icingaCheckbox($name, $value = null, $attribs = null, array $checkedOptions = null)
    {
        if (! isset($attribs['id'])) {
            $attribs['id'] = $this->view->protectId('icingaCheckbox_' . $name);
        }

        $attribs['class'] = (isset($attribs['class']) ? $attribs['class'] . ' ' : '') . 'sr-only';
        $html = parent::formCheckbox($name, $value, $attribs, $checkedOptions);

        $class = 'toggle-switch';
        if (isset($attribs['disabled'])) {
            $class .= ' disabled';
        }

        return $html
            . '<label for="'
            . $attribs['id']
            . '" class="'
            . $class
            . '"><span class="toggle-slider"></span></label>';
    }
}
