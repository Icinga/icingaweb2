<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Exception;

/**
 * Class ProgrammingError
 * @package Icinga\Exception
 */
class SystemPermissionException extends \Exception
{
    public $action;
    public $target;

    public function __construct($message, $action, $target = "")
    {
        parent::__construct($message);
        $this->action = $action;
        $this->target = $target;
    }
}
