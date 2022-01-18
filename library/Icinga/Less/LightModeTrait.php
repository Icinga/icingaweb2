<?php

namespace Icinga\Less;

trait LightModeTrait
{
    /** @var LightMode */
    private $lightMode;

    /**
     * @return LightMode
     */
    public function getLightMode()
    {
        return $this->lightMode;
    }

    /**
     * @param LightMode $lightMode
     *
     * @return $this
     */
    public function setLightMode(LightMode $lightMode)
    {
        $this->lightMode = $lightMode;

        return $this;
    }
}
