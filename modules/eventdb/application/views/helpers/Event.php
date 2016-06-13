<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

use Icinga\Module\Eventdb\Event;

class Zend_View_Helper_Event extends Zend_View_Helper_Abstract
{
    public function event($data)
    {
        return Event::fromData($data);
    }
}
