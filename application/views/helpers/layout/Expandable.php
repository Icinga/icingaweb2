<?php

class Zend_View_Helper_Expandable extends Zend_View_Helper_Abstract
{
    private $CONTROL_BOX_CLASS = "expand-controls";

    const ADD_CLASS = "class";
    const STYLE = "style";
    const IS_EXPANDED = "isExpanded";

    public function expandable($title, $content = "",$params = array()) {
        if (empty($title)) {
            return '';
        }
        $class = "";
        $style = "";
        $expanded = "collapsed";
        if(isset($params[self::ADD_CLASS]))
            $class = $params[self::ADD_CLASS];
        if(isset($params[self::STYLE]))
            $style = $params[self::STYLE];
        if(isset($params[self::IS_EXPANDED]))
            $expanded = $params[self::IS_EXPANDED] ? "" : "collapsed";
        if (isset($params['collapsed']) && $params['collapsed'] === false) {
            $expanded = '';
        }
        if(empty($content) || $content === $title) {
            return "\n<div class='expandable $class' style='$style'>
                <div class='expand-title'>$title</div>
            </div>";
        }
        $controls = $this->getControlDOM();
        $skeleton = "
        <div class='expandable $expanded $class' style='$style'>
            <div class='expand-title'>$title $controls</div>

            <div class='expand-content'>
                $content
            </div>
        </div>";
        return $skeleton;
    }

    public function getControlDOM() {

        $features = "
            <a href='#' class='expand-link' target='_self' title='"._('Click to expand')."'>
                <i class='icon-chevron-up'></i>
            </a>
            <a href='#' class='collapse-link' target='_self' title='"._('Click to collapse')."'>
                <i class='icon-chevron-down'></i>
            </a>
        ";


        return "<div class='{$this->CONTROL_BOX_CLASS}'>$features</div>";
    }
}
