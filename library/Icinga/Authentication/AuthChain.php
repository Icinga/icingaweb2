<?php

namespace Icinga\Authentication;

use Iterator;
use Zend_Config;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;

class AuthChain implements Iterator
{
    private $config;

    private $currentBackend;

    public function __construct(Zend_Config $config)
    {
        $this->config = $config;
    }

    public function rewind()
    {
        $this->config->rewind();
    }

    public function current()
    {
        return $this->currentBackend;
    }

    public function key()
    {
        return $this->config->key();
    }

    public function next()
    {
        $this->config->next();
    }

    public function valid()
    {
        if (!$this->config->valid()) {
            return false;
        }
        $backendConfig = $this->config->current();
        if ((bool) $backendConfig->get('disabled', false) === true) {
            $this->next();
            return $this->valid();
        }
        try {
            $name = $this->key();
            $backend = UserBackend::create($name, $backendConfig);
        } catch (ConfigurationError $e) {
            Logger::exception(
                new ConfigurationError(
                    'Cannot create authentication backend "' . $name . '". An exception was thrown:', 0, $e
                )
            );
            $this->next();
            return $this->valid();
        }
        $this->currentBackend = $backend;
        return true;
    }
}
