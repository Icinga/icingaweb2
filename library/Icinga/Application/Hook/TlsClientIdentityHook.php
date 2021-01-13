<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

/**
 * TLS client identity hook base class
 *
 * Extend this class if you want to prevent TLS client identities used by your module from being removed.
 */
abstract class TlsClientIdentityHook
{
    /**
     * Constructor
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Overwrite this function if you want to do some initialization stuff
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Called before the given client identity is removed
     *
     * If an exception is thrown, the removal fails.
     *
     * @param   string      $clientIdentityName
     *
     * @throws  \Exception
     */
    abstract public function beforeRemove($clientIdentityName);

    /**
     * Called before a client identity is renamed as given
     *
     * If an exception is thrown, the renaming fails.
     *
     * @param   string      $oldClientIdentityName
     * @param   string      $newClientIdentityName
     *
     * @throws  \Exception
     */
    abstract public function beforeRename($oldClientIdentityName, $newClientIdentityName);
}
