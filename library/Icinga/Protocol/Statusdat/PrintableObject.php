<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

class PrintableObject
{
    public function __toString()
    {
        if (isset($this->contact_name)) {
            return $this->contact_name;
        } elseif (isset($this->service_description)) {
            return $this->service_description;
        } elseif (isset($this->host_name)) {
            return $this->host_name;
        }
        return '';
    }
}
