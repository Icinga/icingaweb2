<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

/**
 * Helper for creating ticket links from ticket hooks
 */
class Zend_View_Helper_CreateTicketLinks extends Zend_View_Helper_Abstract
{
    /**
     * Create ticket links form ticket hooks
     *
     * @param   string $text
     *
     * @return  string
     * @see     \Icinga\Web\Hook\TicketHook::createLinks()
     */
    public function createTicketLinks($text)
    {
        $tickets = $this->view->tickets;
        /** @var \Icinga\Web\Hook\TicketHook $tickets */
        return isset($tickets) ? $tickets->createLinks($text) : $text;
    }
}
