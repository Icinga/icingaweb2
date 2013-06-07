<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Exception\ConfigurationError;

/**
 * Class Logger
 * @package Icinga\Application
 */
class Logger
{
    /**
     *
     */
    const DEFAULT_LOG_TYPE = "stream";

    /**
     *
     */
    const DEFAULT_LOG_TARGET = "./var/log/icinga.log";

    /**
     *
     */
    const DEFAULT_DEBUG_TARGET = "./var/log/icinga.debug.log";

    /**
     * @var array
     */
    private $writers = array();

    /**
     * @var null
     */
    private $logger = null;

    /**
     * @var
     */
    private static $instance;

    /**
     * @var array
     */
    private static $queue = array();

    /**
     * @param \Zend_Config $config
     */
    public function __construct(\Zend_Config $config)
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
     * @param \Zend_Config $config
     */
    public function overwrite(\Zend_Config $config)
    {
        $this->clearLog();
        try {
            if ($config->debug && $config->debug->enable == 1) {
                $this->setupDebugLog($config);
            }
        } catch (ConfigurationError $e) {
            $this->warn("Could not create debug log: {$e->getMessage()}");
        }

        $this->setupLog($config);
        $this->flushQueue();
    }

    /**
     * @param \Zend_Config $config
     */
    private function setupDebugLog(\Zend_Config $config)
    {
        $type = $config->debug->get("type", self::DEFAULT_LOG_TYPE);
        $target = $config->debug->get("target", self::DEFAULT_LOG_TARGET);
        if ($target == self::DEFAULT_LOG_TARGET) {
            $type == self::DEFAULT_LOG_TYPE;
        }

        $this->addWriter($type, $target, \Zend_Log::DEBUG);
    }

    /**
     * @param \Zend_Config $config
     */
    private function setupLog(\Zend_Config $config)
    {
        $type = $config->get("type", self::DEFAULT_LOG_TYPE);
        $target = $config->get("target", self::DEFAULT_DEBUG_TARGET);
        if ($target == self::DEFAULT_DEBUG_TARGET) {
            $type == self::DEFAULT_LOG_TYPE;
        }
        $level = \Zend_Log::WARN;
        if ($config->get("verbose", 0) == 1) {
            $level = \Zend_Log::INFO;
        }
        $this->addWriter($type, $target, $level);
    }

    /**
     * @param $type
     * @param $target
     * @param $priority
     * @throws ConfigurationError
     */
    private function addWriter($type, $target, $priority)
    {
        $type[0] = strtoupper($type[0]);
        $writerClass = "\Zend_Log_Writer_" . $type;
        if (!class_exists($writerClass)) {
            throw new ConfigurationError("Could not create log: Unknown type " . $type);
        }

        $writer = new $writerClass($target);

        $writer->addFilter(new \Zend_Log_Filter_Priority($priority));
        $this->logger->addWriter($writer);
        $this->writers[] = $writer;
    }

    /**
     * flushQueue
     */
    public function flushQueue()
    {
        foreach (self::$queue as $msgTypePair) {
            $this->logger->log($msgTypePair[0], $msgTypePair[1]);
        }
    }

    /**
     * @param array $argv
     * @return string
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
     * clearLog
     */
    public function clearLog()
    {
        $this->logger = null;
        $this->writers = array();
        $this->logger = new \Zend_Log();
    }

    /**
     * @param \Zend_Config $config
     * @return Logger
     */
    public static function create(\Zend_Config $config)
    {
        if (self::$instance) {
            return self::$instance->overwrite($config);
        }
        return self::$instance = new Logger($config);
    }

    /**
     * debug
     */
    public static function debug()
    {
        self::log(self::formatMessage(func_get_args()), \Zend_Log::DEBUG);
    }

    /**
     *
     */
    public static function warn()
    {
        self::log(self::formatMessage(func_get_args()), \Zend_Log::WARN);
    }

    /**
     *
     */
    public static function info()
    {
        self::log(self::formatMessage(func_get_args()), \Zend_Log::INFO);
    }

    /**
     *
     */
    public static function error()
    {
        self::log(self::formatMessage(func_get_args()), \Zend_Log::ERR);
    }

    /**
     *
     */
    public static function fatal()
    {
        self::log(self::formatMessage(func_get_args()), \Zend_Log::EMERG);
    }

    /**
     * @param $msg
     * @param int $level
     */
    private static function log($msg, $level = \Zend_Log::INFO)
    {
        $logger = self::$instance;

        if (!$logger) {
            array_push(self::$queue, array($msg, $level));
            return;
        }

        $logger->logger->log($msg, $level);
    }

    /**
     *
     */
    public static function reset()
    {
        self::$queue = array();
        self::$instance = null;
    }
}
