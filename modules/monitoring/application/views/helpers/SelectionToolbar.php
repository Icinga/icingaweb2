<?php

class Zend_View_Helper_SelectionToolbar extends Zend_View_Helper_Abstract
{
    /**
     * Create a selection toolbar
     *
     * @param      $type
     * @param null $target
     *
     * @return string
     */
    public function selectionToolbar($type, $target = null)
    {
        if ($type == 'multi') {
            return '<div class="selection-toolbar"> Select '
                . '<a href="' . $target . '"> All </a>'
                . '<a href="#"> None </a> </div>';
        } else if ($type == 'single') {
            return '<div class="selection-toolbar"> Select <a href="#"> None </a> </div>';
        } else {
            return '';
        }
    }
}
