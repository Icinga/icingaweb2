<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook;

use Icinga\Application\Logger as Logger;

/**
 * Class Toptray
 * @package Icinga\Web\Hook
 */
abstract class Toptray
{
    /**
     *
     */
    const ALIGN_LEFT = "pull-left";

    /**
     *
     */
    const ALIGN_NONE = "";

    /**
     *
     */
    const ALIGN_RIGHT = "pull-right";

    /**
     * @var string
     */
    protected $align = self::ALIGN_NONE;

    /**
     * @param $align
     */
    public function setAlignment($align)
    {
        $this->align = $align;
    }

    /**
     * @return string
     */
    final public function getWidgetDOM()
    {
        try {
            return '<ul class="nav ' . $this->align . '" >' . $this->buildDOM() . '</ul>';
        } catch (\Exception $e) {
            Logger::error("Could not create tray widget : %s", $e->getMessage());
            return '';
        }


    }

    /**
     * @return mixed
     */
    abstract protected function buildDOM();
}
