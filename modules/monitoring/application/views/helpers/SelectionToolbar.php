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
            return '<div class="selection-toolbar">'
                . '<a href="' . $target . '" data-base-target="_next"> Select All </a> </div>';
        } else {
            return '';
        }
    }
}
