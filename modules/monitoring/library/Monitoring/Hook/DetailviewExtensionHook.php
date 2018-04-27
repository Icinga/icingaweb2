<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Application\ClassLoader;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\View;

/**
 * Base class for hooks extending the detail view of monitored objects
 *
 * Extend this class if you want to extend the detail view of monitored objects with custom HTML.
 */
abstract class DetailviewExtensionHook extends BaseViewExtensionHook
{
    /**
     * Create a new hook
     *
     * @see init() For hook initialization.
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Overwrite this function for hook initialization, e.g. loading the hook's config
     */
    protected function init()
    {
    }

    /**
     * Shall return valid HTML to include in the detail view
     *
     * @param   MonitoredObject $object     The object to generate HTML for
     *
     * @return  string
     */
    abstract public function getHtmlForObject(MonitoredObject $object);
}
