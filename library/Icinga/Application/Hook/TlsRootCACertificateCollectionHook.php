<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

/**
 * TLS root CA certificate collection hook base class
 *
 * Extend this class if you want to prevent TLS root CA certificate collections used by your module from being removed.
 */
abstract class TlsRootCACertificateCollectionHook
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
     * Called before the given root CA certificate collection is removed
     *
     * If an exception is thrown, the removal fails.
     *
     * @param   string      $collectionName
     *
     * @throws  \Exception
     */
    abstract public function beforeRemove($collectionName);

    /**
     * Called before a root CA certificate collection is renamed as given
     *
     * If an exception is thrown, the renaming fails.
     *
     * @param   string      $oldCollectionName
     * @param   string      $newCollectionName
     *
     * @throws  \Exception
     */
    abstract public function beforeRename($oldCollectionName, $newCollectionName);
}
