<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook;

/**
 * Icinga Web Ticket Hook base class
 *
 * Extend this class if you want to integrate your ticketing solution nicely into
 * Icinga Web
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
abstract class Ticket
{
    /**
     * Constructor must live without arguments right now
     *
     * Therefore the constructor is final, we might change our opinion about
     * this one far day
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Overwrite this function if you want to do some initialization stuff
     *
     * @return void
     */
    protected function init()
    {
    }

    abstract public function getPattern();

    abstract public function createLink($match);

}
