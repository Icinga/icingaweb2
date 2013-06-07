<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

/**
 * Class RuntimeStateContainer
 * @package Icinga\Protocol\Statusdat
 */
class RuntimeStateContainer extends \stdClass
{
    /**
     * @var string
     */
    public $runtimeState = "";

    /**
     * @param string $str
     */
    public function __construct($str = "")
    {
        $this->runtimeState = $str;
    }

    /**
     * @param $attr
     * @return bool
     */
    public function __isset($attr)
    {
        try {
            $this->__get($attr);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param $attr
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($attr)
    {
        $start = strpos($this->runtimeState, $attr . "=");
        if ($start === false) {
            throw new \InvalidArgumentException("Unknown property $attr");
        }

        $start += strlen($attr . "=");
        $len = strpos($this->runtimeState, "\n", $start) - $start;
        $this->$attr = trim(substr($this->runtimeState, $start, $len));

        return $this->$attr;
    }
}
