<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

class Expression
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }
}
