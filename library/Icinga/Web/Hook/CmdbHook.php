<?php
namespace Icinga\Web\Hook;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Monitoring\Object\MonitoredObject;

abstract class CmdbHook extends WebBaseHook
{
    final public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    public function getHtml(MonitoredObject $object)
    {
        throw new ProgrammingError('This function needs to be implemented in Hook.');
    }
}
