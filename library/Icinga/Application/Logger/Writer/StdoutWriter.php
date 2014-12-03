<?php

namespace Icinga\Application\Logger\Writer;

use Icinga\Cli\Screen;
use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;
use Zend_Config;

/**
 * Class to write log messages to STDOUT
 */
class StdoutWriter extends LogWriter
{
    protected $screen;

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
        $color = 'black';
        switch ($severity) {
            case Logger::ERROR:
                $color = 'red';
                break;
            case Logger::WARNING:
                $color = 'orange';
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
