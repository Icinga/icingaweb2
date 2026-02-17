<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Exception;
use Icinga\Application\Logger;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Exception\IcingaException;
use ipl\Web\Url;
use LogicException;

abstract class HealthHook
{
    use Essentials;

    /** @var int */
    const STATE_OK = 0;

    /** @var int */
    const STATE_WARNING = 1;

    /** @var int */
    const STATE_CRITICAL = 2;

    /** @var int */
    const STATE_UNKNOWN = 3;

    /** @var int The overall state */
    protected $state;

    /** @var string Message describing the overall state */
    protected $message;

    /** @var array Available metrics */
    protected $metrics;

    /** @var Url Url to a graphical representation of the available metrics */
    protected $url;

    protected static function getHookName(): string
    {
        return 'health';
    }

    /**
     * Get overall state
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set overall state
     *
     * @param int $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the message describing the overall state
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the message describing the overall state
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get available metrics
     *
     * @return array
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * Set available metrics
     *
     * @param array $metrics
     *
     * @return $this
     */
    public function setMetrics(array $metrics)
    {
        $this->metrics = $metrics;

        return $this;
    }

    /**
     * Get the url to a graphical representation of the available metrics
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the url to a graphical representation of the available metrics
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Collect available health data from hooks
     *
     * @return ArrayDatasource
     */
    final public static function collectHealthData()
    {
        $checks = [];
        foreach (static::all() as $hook) {
            try {
                $hook->checkHealth();
                $url = $hook->getUrl();
                $state = $hook->getState();
                $message = $hook->getMessage();
                $metrics = $hook->getMetrics();
            } catch (Exception $e) {
                Logger::error('Failed to check health: %s', $e);

                $state = self::STATE_UNKNOWN;
                $message = IcingaException::describe($e);
                $metrics = null;
                $url = null;
            }

            $checks[] = (object) [
                'module'    => $hook->getModuleName(),
                'name'      => $hook->getName(),
                'url'       => $url ? $url->getAbsoluteUrl() : null,
                'state'     => $state,
                'message'   => $message,
                'metrics'   => (object) $metrics
            ];
        }

        return (new ArrayDatasource($checks))
            ->setKeyColumn('name');
    }

    /**
     * Get the name of the hook
     *
     * Only used in API responses to differentiate it from other hooks of the same module.
     *
     * @return string
     */
    public function getName()
    {
        $classPath = get_class($this);
        $parts = explode('\\', $classPath);
        $className = array_pop($parts);

        if (substr($className, -4) === 'Hook') {
            $className = substr($className, 1, -4);
        }

        return strtolower($className[0]) . substr($className, 1);
    }

    /**
     * Get the name of the module providing this hook
     *
     * @return string
     *
     * @throws LogicException
     */
    public function getModuleName()
    {
        $classPath = get_class($this);
        if (substr($classPath, 0, 14) !== 'Icinga\\Module\\') {
            throw new LogicException('Not a module hook');
        }

        $withoutPrefix = substr($classPath, 14);
        return strtolower(substr($withoutPrefix, 0, strpos($withoutPrefix, '\\')));
    }

    /**
     * Check health
     *
     * Implement this method and set the overall state, message, url and metrics.
     *
     * @return void
     */
    abstract public function checkHealth();
}
