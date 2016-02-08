<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Interface defining a factory which is configured at runtime
 */
interface ConfigAwareFactory
{
    /**
     * Set the factory's config
     *
     * @param   mixed   $config
     * @throws  \Icinga\Exception\ConfigurationError if the given config is not valid
     */
    public static function setConfig($config);
}
