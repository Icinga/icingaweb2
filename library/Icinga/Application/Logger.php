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

use Icinga\Protocol\Ldap\Exception;
use \Zend_Config;
use \Zend_Log;
use \Zend_Log_Filter_Priority;
use \Zend_Log_Writer_Abstract;
use \Zend_Log_Exception;
use \Icinga\Exception\ConfigurationError;

/**
 * Singleton logger
 */
final class Logger
{
    /**
     * Default log type
     */
    const DEFAULT_LOG_TYPE = "stream";

    /**
     * Default log target
     */
    const DEFAULT_LOG_TARGET = "./var/log/icingaweb.log";

    /**
     * Default debug target
     */
    const DEFAULT_DEBUG_TARGET = "./var/log/icingaweb.debug.log";

    /**
     * Array of writers
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
     * Singleton instance
     *
     * @var Logger
     */
    private static $instance;

    /**
     * Queue of unwritten messages
     *
     * @var array
     */
    private static $queue = array();

    /**
     * Flag indicate that errors occurred in the past
     *
     * @var bool
     */
    private static $errorsOccurred = false;

    /**
     * Create a new logger object
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->overwrite($config);
    }

    /**
     * @return array
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * Overwrite config to initiated logger
     *
     * @param   Zend_Config $config
     *
     * @return  self
     */
    public function overwrite(Zend_Config $config)
    {
        $this->clearLog();
        try {
            if ($config->debug && $config->debug->enable == '1') {
                $this->setupDebugLog($config);
            }
        } catch (ConfigurationError $e) {
            $this->warn('Could not create debug log: ' . $e->getMessage());
        }
        if ($config->get('enable', '1') != '0') {
            $this->setupLog($config);
        }
        $this->flushQueue();

        return $this;
    }

    /**
     * Configure debug log
     *
     * @param Zend_Config $config
     */
    private function setupDebugLog(Zend_Config $config)
    {
        $type = $config->debug->get("type", self::DEFAULT_LOG_TYPE);
        $target = $config->debug->get("target", self::DEFAULT_LOG_TARGET);
        if ($target == self::DEFAULT_LOG_TARGET) {
            $type = self::DEFAULT_LOG_TYPE;
        }
        $this->addWriter($type, $target, Zend_Log::DEBUG);
    }

    /**
     * Configure log
     *
     * @param Zend_Config $config
     */
    private function setupLog(Zend_Config $config)
    {
        $type = $config->get("type", self::DEFAULT_LOG_TYPE);
        $target = $config->get("target", self::DEFAULT_DEBUG_TARGET);
        if ($target == self::DEFAULT_DEBUG_TARGET) {
            $type = self::DEFAULT_LOG_TYPE;
        }
        $level = Zend_Log::WARN;
        if ($config->get("verbose", 0) == 1) {
            $level = Zend_Log::INFO;
        }
        $this->addWriter($type, $target, $level);
    }

    /**
     * Add writer to log instance
     *
     * @param   string  $type       Type, e.g. stream
     * @param   string  $target     Target, e.g. filename
     * @param   int     $priority   Value of Zend::* constant
     * @throws  ConfigurationError
     */
    private function addWriter($type, $target, $priority)
    {
        $type[0] = strtoupper($type[0]);
        $writerClass = "Zend_Log_Writer_" . $type;

        if (!@class_exists($writerClass)) {
            self::fatal(
                'Could not add log writer of type "%s". Type does not exist.',
                $type
            );
            return;
        }
        try {

            $target = Config::resolvePath($target);
            $writer = new $writerClass($target);
            $writer->addFilter(new Zend_Log_Filter_Priority($priority));
            // Make sure the permissions for log target file are correct
            if ($type === 'Stream' && substr($target, 0, 6) !== 'php://' && !file_exists($target)) {
                touch($target);
                chmod($target, 0664);
            }

            $this->logger->addWriter($writer);
            $this->writers[] = $writer;
        } catch (Zend_Log_Exception $e) {
            self::fatal(
                'Could not add log writer of type %s. An exception was thrown: %s',
                $type,
                $e->getMessage()
            );
        }
    }

    /**
     * Flush pending messages to writer
     */
    public function flushQueue()
    {
        try {
            foreach (self::$queue as $msgTypePair) {
                $this->logger->log($msgTypePair[0], $msgTypePair[1]);
            }
        } catch (Zend_Log_Exception $e) {
            self::fatal(
                'Could not flush logs to output. An exception was thrown: %s',
                $e->getMessage()
            );
        }
    }

    /**
     * Format output message
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
     * Reset object configuration
     */
    public function clearLog()
    {
        $this->logger = null;
        $this->writers = array();
        $this->logger = new Zend_Log();
    }

    /**
     * Create an instance
     *
     * @param   Zend_Config $config
     *
     * @return  Logger
     */
    public static function create(Zend_Config $config)
    {
        if (self::$instance) {
            return self::$instance->overwrite($config);
        }
        return self::$instance = new Logger($config);
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
     * Log message with severity fatal
     */
    public static function fatal()
    {
        self::log(self::formatMessage(func_get_args()), Zend_Log::EMERG);
    }

    /**
     * Log message
     *
     * @param string    $msg    Message
     * @param int       $level  Log level
     */
    private static function log($msg, $level = Zend_Log::INFO)
    {
        $logger = self::$instance;

        if ($level < Zend_Log::WARN && self::$errorsOccurred === false) {
            self::$errorsOccurred =true;
        }

        if (!$logger || !count($logger->getWriters())) {
            array_push(self::$queue, array($msg, $level));
            return;
        }

        $logger->logger->log($msg, $level);
    }

    /**
     * Flag if messages > warning occurred
     *
     * @return bool
     */
    public static function hasErrorsOccurred()
    {
        return self::$errorsOccurred;
    }

    /**
     * Access the log queue
     *
     * The log queue holds messages that could not be written to output
     *
     * @return array
     */
    public static function getQueue()
    {
        return self::$queue;
    }

    /**
     * Reset object state
     */
    public static function reset()
    {
        self::$queue = array();
        self::$instance = null;
    }
}
