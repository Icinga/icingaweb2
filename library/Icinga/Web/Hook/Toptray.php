<?php

namespace Icinga\Web\Hook;
use Icinga\Application\Logger as Logger;

abstract class Toptray {
    const ALIGN_LEFT = "pull-left";
    const ALIGN_NONE = "";
    const ALIGN_RIGHT =  "pull-right";
    protected $align = self::ALIGN_NONE;

    public function setAlignment($align)
    {
        $this->align = $align;
    }

    final public function getWidgetDOM()
    {
        try {
            return '<ul class="nav '.$this->align.'" >'.$this->buildDOM().'</ul>';
        } catch (\Exception $e) {
            Logger::error("Could not create tray widget : %s",$e->getMessage());
            return '';
        }


    }

    abstract protected function buildDOM();
}
