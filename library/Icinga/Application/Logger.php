<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Exception;
use Zend_Config;
use Zend_Log;
use Zend_Log_Exception;
use Zend_Log_Filter_Priority;
use Zend_Log_Writer_Abstract;
use Icinga\Exception\ConfigurationError;
use Icinga\Util\File;

/**
 * Zend_Log wrapper
 */
class Logger
{
    /**
     * Writers attached to the instance of Zend_Log
     *
     * @var array
     */
    private $writers = array();

    /**
     * Instance of Zend_Log
     *
     * @var Zend_Log
     */
    private $logger;

    /**
     * Singleton Logger instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Format for logging exceptions
     */
    const LOG_EXCEPTION_FORMAT = <<<'EOD'
%s: %s

Stacktrace
----------
%s
EOD;

    /**
     * Create a new Logger
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->logger = new Zend_Log();
        if ((bool) $config->get('enable', true) === true) {
            $this->addWriter($config);
        }
    }

    /**
     * Get the writers attached to the instance of Zend_Log
     *
     * @return array
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * Add writer to the Zend_Log instance
     *
     * @param   Zend_Config $config
     *
     * @throws  ConfigurationError
     */
    public function addWriter($config)
    {
        if (($type = $config->type) === null) {
            throw new ConfigurationError('Logger configuration is missing the type directive');
        }
        $type = ucfirst(strtolower($type));
        $writerClass = 'Zend_Log_Writer_' . $type;
        if (!@class_exists($writerClass)) {
            throw new ConfigurationError('Cannot add log writer of type "' . $type . '". Type does not exist');
        }
        try {
            switch ($type) {
                case 'Stream':
                    if (($target = $config->target) === null) {
                        throw new ConfigurationError(
                            'Logger configuration is missing the target directive for type stream'
                        );
                    }
                    $target = Config::resolvePath($target);
                    $writer = new $writerClass($target);
                    if (substr($target, 0, 6) !== 'php://' && !file_exists($target)) {
                        File::create($target);
                    }
                    break;
                case 'Syslog':
                    $writer = new $writerClass($config->toArray());
                    break;
                default:
                    throw new ConfigurationError('Logger configuration defines an invalid log type "' . $type . '"');
            }
            if (($priority = $config->priority) === null) {
                $priority = Zend_Log::WARN;
            } else {
                $priority = (int) $priority;
            }
            $writer->addFilter(new Zend_Log_Filter_Priority($priority));
            $this->logger->addWriter($writer);
            $this->writers[] = $writer;
        } catch (Zend_Log_Exception $e) {
            throw new ConfigurationError(
                'Cannot not add log writer of type "' . $type . '". An exception was thrown: '.  $e->getMessage()
            );
        }
    }

    /**
     * Format a message
     *
     * @param   array $argv
     *
     * @return  string
     */
    public static function formatMessage(array $argv)
    {
        if (count($argv) == 1) {
            $format = $argv[0];
        } else {
            $format = array_shift($argv);
        }
        if (!is_string($format)) {
            $format = json_encode($format);
        }
        foreach ($argv as &$arg) {
            if (!is_string($arg)) {
                $arg = json_encode($arg);
            }
        }

        return @vsprintf($format, $argv);
    }

    /**
     * Create/overwrite the internal Logger instance
     *
     * @param Zend_Config $config
     */
    public static function create(Zend_Config $config)
    {
        self::$instance = new static($config);
    }

    /**
     * Log message with severity debug
     */
    public static function debug()
    {
        self::log(self::formatMessage(func_get_args()), Zend_Log::DEBUG);
    }

    /**
     * Log message with severity warn
     */
    public static function warn()
    {
        self::log(self::formatMessage(func_get_args()), Zend_Log::WARN);
    }

    /**
     * Log message with severity info
     */
    public static function info()
    {
        self::log(self::formatMessage(func_get_args()), Zend_Log::INFO);
    }

    /**
     * Log message with severity error
     */
    public static function error()
    {
        self::log(self::formatMessage(func_get_args()), Zend_Log::ERR);
    }

    /**
     * Log a message at a priority
     *
     * @param  string   $message   Message to log
     * @param  int      $priority  Priority of message
     */
    private static function log($message, $priority = Zend_Log::INFO)
    {
        // Swallow messages if the Logger hast not been created
        if (self::$instance !== null && count(self::$instance->getWriters()) > 0) {
            self::$instance->logger->log($message, $priority);
        }
    }

    /**
     * Log a exception at a priority
     *
     * @param  Exception    $e   Exception to log
     * @param  int          $priority  Priority of message
     */
    public static function exception(Exception $e, $priority = Zend_Log::ERR)
    {
        $message = array();
        do {
            $message[] = self::formatMessage(
                array(self::LOG_EXCEPTION_FORMAT, get_class($e), $e->getMessage(), $e->getTraceAsString())
            );
        } while($e = $e->getPrevious());
        self::log(
            implode(PHP_EOL, $message),
            $priority
        );
    }
}
