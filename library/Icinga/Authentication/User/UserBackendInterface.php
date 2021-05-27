<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Authentication\Authenticatable;
use Icinga\User;

/**
 * Interface for user backends
 */
interface UserBackendInterface extends Authenticatable
{
    /**
     * Set this backend's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name);

    /**
     * Return this backend's name
     *
     * @return  string
     */
    public function getName();

    /**
     * Return this backend's configuration form class path
     *
     * This is not part of the interface to not break existing implementations.
     * If you need a custom backend form, implement this method.
     *
     * @return  string
     */
    //public static function getConfigurationFormClass();
}
