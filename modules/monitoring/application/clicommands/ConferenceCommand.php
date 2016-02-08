<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Clicommands;

use Icinga\Cli\Command;

/**
 * The OSMC 2013 special command
 *
 * This command has been written to impress the audience
 */
class ConferenceCommand extends Command
{
    protected static $flipflop = 0;

    /**
     * Give them a warm welcome
     *
     * Use this command in case you feel that you should be friendly. Should
     * be executed as follows:
     *
     * icingacli monitoring conference welcome --watch=1
     */
    public function welcomeAction()
    {
        self::$flipflop = (int) ! self::$flipflop;
        $signs    = array('☺', '❤');
        $bgcolors = array('blue', 'red');
        $scr      = $this->screen;
        $sign     = $signs[self::$flipflop];
        $bgcolor  = $bgcolors[self::$flipflop];
        echo $scr->clear() . $scr->newlines(10) . $scr->center(
            $scr->colorize(" $sign  Welcome OSMC 2013 $sign  ", 'white', $bgcolor)
        ) . $scr->newlines(10);
    }
}
