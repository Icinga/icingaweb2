<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Iterator;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;
use Icinga\Data\ConfigObject;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\User;

/**
 * Iterate user backends created from config
 */
class AuthChain implements Authenticatable, Iterator
{
    /**
     * Authentication config file
     *
     * @var string
     */
    const AUTHENTICATION_CONFIG = 'authentication';

    /**
     * Error code if the authentication configuration was not readable
     *
     * @var int
     */
    const EPERM = 1;

    /**
     * Error code if the authentication configuration is empty
     */
    const EEMPTY = 2;

    /**
     * Error code if all authentication methods failed
     *
     * @var int
     */
    const EFAIL = 3;

    /**
     * Error code if not all authentication methods were available
     *
     * @var int
     */
    const ENOTALL = 4;

    /**
     * User backends configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * The consecutive user backend while looping
     *
     * @var UserBackendInterface
     */
    protected $currentBackend;

    /**
     * Last error code
     *
     * @var int|null
     */
    protected $error;

    /**
     * Whether external user backends should be skipped on iteration
     *
     * @var bool
     */
    protected $skipExternalBackends = false;

    /**
     * Create a new authentication chain from config
     *
     * @param Config $config User backends configuration
     */
    public function __construct(Config $config = null)
    {
        if ($config === null) {
            try {
                $this->config = Config::app(static::AUTHENTICATION_CONFIG);
            } catch (NotReadableError $e) {
                $this->config = new Config();
                $this->error = static::EPERM;
            }
        } else {
            $this->config = $config;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, $password)
    {
        $this->error = null;
        $backendsTried = 0;
        $backendsWithError = 0;
        foreach ($this as $backend) {
            ++$backendsTried;
            try {
                $authenticated = $backend->authenticate($user, $password);
            } catch (AuthenticationException $e) {
                Logger::error($e);
                ++$backendsWithError;
                continue;
            }
            if ($authenticated) {
                $user->setAdditional('backend_name', $backend->getName());
                $user->setAdditional('backend_type', $this->config->current()->get('backend'));
                return true;
            }
        }
        if ($backendsTried === 0) {
            $this->error = static::EEMPTY;
        } elseif ($backendsTried === $backendsWithError) {
            $this->error = static::EFAIL;
        } elseif ($backendsWithError) {
            $this->error = static::ENOTALL;
        }
        return false;
    }

    /**
     * Get the last error code
     *
     * @return int|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Whether authentication had errors
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== null;
    }

    /**
     * Get whether to skip external user backends on iteration
     *
     * @return bool
     */
    public function getSkipExternalBackends()
    {
        return $this->skipExternalBackends;
    }

    /**
     * Set whether to skip external user backends on iteration
     *
     * @param   bool $skipExternalBackends
     *
     * @return  $this
     */
    public function setSkipExternalBackends($skipExternalBackends = true)
    {
        $this->skipExternalBackends = (bool) $skipExternalBackends;
        return $this;
    }

    /**
     * Rewind the chain
     *
     * @return ConfigObject
     */
    public function rewind()
    {
        $this->currentBackend = null;
        return $this->config->rewind();
    }

    /**
     * Get the current user backend
     *
     * @return UserBackendInterface
     */
    public function current()
    {
        return $this->currentBackend;
    }

    /**
     * Get the key of the current user backend config
     *
     * @return string
     */
    public function key()
    {
        return $this->config->key();
    }

    /**
     * Move forward to the next user backend config
     *
     * @return ConfigObject
     */
    public function next()
    {
        return $this->config->next();
    }

    /**
     * Check whether the current user backend is valid, i.e. it's enabled, not an external user backend and whether its
     * config is valid
     *
     * @return bool
     */
    public function valid()
    {
        if (! $this->config->valid()) {
            // Stop when there are no more backends to check
            return false;
        }

        $backendConfig = $this->config->current();
        if ((bool) $backendConfig->get('disabled', false)) {
            $this->next();
            return $this->valid();
        }

        $name = $this->key();
        try {
            $backend = UserBackend::create($name, $backendConfig);
        } catch (ConfigurationError $e) {
            Logger::error(
                new ConfigurationError(
                    'Can\'t create authentication backend "%s". An exception was thrown:',
                    $name,
                    $e
                )
            );
            $this->next();
            return $this->valid();
        }

        if ($this->getSkipExternalBackends()
            && $backend instanceof ExternalBackend
        ) {
            $this->next();
            return $this->valid();
        }

        $this->currentBackend = $backend;
        return true;
    }
}
