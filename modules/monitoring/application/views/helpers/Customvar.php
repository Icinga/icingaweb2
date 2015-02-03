<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

class Zend_View_Helper_Customvar extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return self
     */
    public function checkPerformance()
    {
        return $this;
    }

    public function customvar($struct)
    {
        if (is_string($struct) || is_int($struct) || is_float($struct)) {
            return $this->view->escape((string) $struct);
        } elseif (is_array($struct)) {
            return $this->renderArray($struct);
        } elseif (is_object($struct)) {
            return $this->renderObject($struct);
        }
    }

    protected function renderArray($array)
    {
        if (empty($array)) {
            return '[]';
        }
        $out = "<ul>\n";
        foreach ($array as $val) {
            $out .= '<li>' . $this->customvar($val) . "</li>\n";
        }
        return $out . "</ul>\n";
    }

    protected function renderObject($object)
    {
        if (empty($object)) {
            return '{}';
        }
        $out = "{<ul>\n";
        foreach ($object as $key => $val) {
            $out .= '<li>'
                  . $this->view->escape($key)
                  . ' => '
                  . $this->customvar($val)
                  . "</li>\n";
        }
        return $out . "</ul>}";
    }

}

