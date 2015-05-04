<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Iterator;
use Icinga\Data\ConfigObject;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;

/**
 * Iterate user backends created from config
 */
class AuthChain implements Iterator
{
    /**
     * User backends configuration
     *
     * @var Config
     */
    private $config;

    /**
     * The consecutive user backend while looping
     *
     * @var UserBackendInterface
     */
    private $currentBackend;

    /**
     * Create a new authentication chain from config
     *
     * @param Config $config User backends configuration
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Rewind the chain
     *
     * @return  ConfigObject
     */
    public function rewind()
    {
        $this->currentBackend = null;
        return $this->config->rewind();
    }

    /**
     * Return the current user backend
     *
     * @return UserBackendInterface
     */
    public function current()
    {
        return $this->currentBackend;
    }

    /**
     * Return the key of the current user backend config
     *
     * @return  string
     */
    public function key()
    {
        return $this->config->key();
    }

    /**
     * Move forward to the next user backend config
     *
     * @return  ConfigObject
     */
    public function next()
    {
        return $this->config->next();
    }

    /**
     * Check if the current user backend is valid, i.e. it's enabled and the config is valid
     *
     * @return  bool
     */
    public function valid()
    {
        if (!$this->config->valid()) {
            // Stop when there are no more backends to check
            return false;
        }

        $backendConfig = $this->config->current();
        if ((bool) $backendConfig->get('disabled', false) === true) {
            $this->next();
            return $this->valid();
        }

        $name = $this->key();
        try {
            $backend = UserBackend::create($name, $backendConfig);
        } catch (ConfigurationError $e) {
            Logger::error(
                new ConfigurationError(
                    'Cannot create authentication backend "%s". An exception was thrown:',
                    $name,
                    $e
                )
            );
            $this->next();
            return $this->valid();
        }

        $this->currentBackend = $backend;
        return true;
    }
}
