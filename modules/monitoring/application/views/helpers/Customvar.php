<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

use Icinga\Web\View;

class Zend_View_Helper_Customvar extends Zend_View_Helper_Abstract
{
    /** @var View */
    public $view;

    /**
     * Create dispatch instance
     *
     * @return $this
     */
    public function checkPerformance()
    {
        return $this;
    }

    public function customvar($struct)
    {
        if (is_scalar($struct)) {
            return nl2br($this->view->escape(
                is_string($struct)
                    ? $struct
                    : var_export($struct, true)
            ), false);
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
        if (0 === count((array) $object)) {
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
