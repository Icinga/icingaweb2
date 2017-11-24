<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

/**
 * Resource hook base class
 *
 * Extend this class if you want to prevent resources used by your module from being removed.
 */
abstract class ResourceHook
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
     */
    protected function init()
    {
    }

    /**
     * Called before the given resource is removed
     *
     * If an exception is thrown, the removal fails.
     *
     * @param   string      $resourceName
     *
     * @throws  \Exception
     */
    abstract public function beforeRemove($resourceName);

    /**
     * Called before a resource is renamed as given
     *
     * If an exception is thrown, the renaming fails.
     *
     * @param   string      $oldResourceName
     * @param   string      $newResourceName
     *
     * @throws  \Exception
     */
    abstract public function beforeRename($oldResourceName, $newResourceName);
}
