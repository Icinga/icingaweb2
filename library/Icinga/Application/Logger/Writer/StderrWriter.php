<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Logger\Writer;

use Icinga\Cli\Screen;
use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;

/**
 * Class to write log messages to STDERR
 */
class StderrWriter extends LogWriter
{
    /**
     * The current Screen in use
     *
     * @var Screen
     */
    protected $screen;

    /**
     * Return the current Screen
     *
     * @return  Screen
     */
    protected function screen()
    {
        if ($this->screen === null) {
            $this->screen = Screen::instance();
        }

        return $this->screen;
    }

    /**
     * Log a message with the given severity
     *
     * @param   int     $severity   The severity to use
     * @param   string  $message    The message to log
     */
    public function log($severity, $message)
    {
        switch ($severity) {
            case Logger::ERROR:
                $color = 'red';
                break;
            case Logger::WARNING:
                $color = 'yellow';
                break;
            case Logger::INFO:
                $color = 'green';
                break;
            case Logger::DEBUG:
                $color = 'blue';
                break;
        }

        file_put_contents('php://stderr', $this->screen()->colorize($message, $color) . "\n");
    }
}
