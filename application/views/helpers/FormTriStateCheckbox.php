<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * Helper to generate a "datetime" element
 */
class Zend_View_Helper_FormTriStateCheckbox extends Zend_View_Helper_FormElement
{
    /**
     * Generate a tri-state checkbox
     *
     * @param   string  $name       The element name
     * @param   int     $value      The checkbox value
     * @param   array   $attribs    Attributes for the element tag
     *
     * @return  string  The element XHTML
     */
    public function formTriStateCheckbox($name, $value = null, $attribs = null)
    {
        $class = "";
        $xhtml = '<div class="tristate">'
                    . '<div>' . ($value == 1 ? ' ' : ($value === 'unchanged' ? ' ' : ' ' )) . '</div>'

                    . '<input class="' . $class . '" type="radio" value=1 name="'
                        . $name . '" ' . ($value == 1 ? 'checked' : '') . ' ">On</input> '

                    . '<input class="' . $class . '" type="radio" value=0 name="'
                        . $name . '" ' . ($value == 0 ? 'checked' : '') . ' ">Off</input> ';

        if ($value === 'unchanged') {
            $xhtml = $xhtml . '<input class="' . $class . '" type="radio" value="unchanged" name="'
            . $name . '" ' . 'checked "> Undefined </input>';
        };
        return $xhtml . '</div>';
    }
}
