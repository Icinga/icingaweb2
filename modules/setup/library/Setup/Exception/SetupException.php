<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Exception;

use Icinga\Exception\IcingaException;

/**
 * Class SetupException
 *
 * Used to indicate that a setup should be aborted.
 */
class SetupException extends IcingaException
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('Setup abortion');
    }
}
