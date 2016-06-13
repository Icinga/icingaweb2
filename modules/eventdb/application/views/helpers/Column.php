<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

use Icinga\Module\Eventdb\Event;

class Zend_View_Helper_Column extends Zend_View_Helper_Abstract
{
    public function column($column, Event $event)
    {
        switch ($column) {
            case 'priority':
                $html = sprintf('<td class="priority-%1$s">%1$s</td>', $this->view->escape($event->priority));
                break;
            default:
                $html = sprintf('<td>%s</td>', $this->view->escape($event->$column));
                break;
        }

        return $html;
    }
}
