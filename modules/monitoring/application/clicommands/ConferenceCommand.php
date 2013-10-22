<?php

namespace Icinga\Module\Monitoring\Clicommands;

use Icinga\Cli\Command;

/**
 * The OSMC 2013 special command
 *
 * This command has been written to impress the audience
 */
class ConferenceCommand extends Command
{
    /**
     * Give them a warm welcome
     *
     * Use this command in case you feel that you should be friendly
     */
    public function welcomeAction()
    {
        $scr = $this->screen;
        echo $scr->clear() . $scr->newlines(10) . $scr->center(
            $scr->colorize(' ❤ Welcome OSMC 2013 ❤ ', 'white', 'red')
        ) . $scr->newlines(10);
    }
}
