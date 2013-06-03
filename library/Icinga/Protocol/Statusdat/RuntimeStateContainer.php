<?php

namespace Icinga\Protocol\Statusdat;

class RuntimeStateContainer extends \stdClass
{
    public $runtimeState = "";
    public function __construct($str = "") {
        $this->runtimeState = $str;
    }

    public function __isset($attr)
    {
        try {
            $this->__get($attr);
            return true;
        } catch(\InvalidArgumentException $e) {
            return false;
        }
    }

    public function __get($attr)
    {

        $start = strpos($this->runtimeState,$attr."=");
        if($start === False)
            throw new \InvalidArgumentException("Unknown property $attr");

        $start += strlen($attr."=");
        $len = strpos($this->runtimeState,"\n",$start) - $start;
        $this->$attr = trim(substr($this->runtimeState,$start,$len));

        return $this->$attr;
    }
}
