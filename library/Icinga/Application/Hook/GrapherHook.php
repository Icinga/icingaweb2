<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Icinga Web Grapher Hook base class
 *
 * Extend this class if you want to integrate your graphing solution nicely into
 * Icinga Web.
 */
abstract class GrapherHook extends WebBaseHook
{
    /**
     * Whether this grapher provides previews
     *
     * @var bool
     */
    protected $hasPreviews = false;

    /**
     * Whether this grapher provides tiny previews
     *
     * @var bool
     */
    protected $hasTinyPreviews = false;

    /**
     * Constructor must live without arguments right now
     *
     * Therefore the constructor is final, we might change our opinion about
     * this one far day
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
     * Whether this grapher provides previews
     *
     * @return bool
     */
    public function hasPreviews()
    {
        return $this->hasPreviews;
    }

    /**
     * Whether this grapher provides tiny previews
     *
     * @return bool
     */
    public function hasTinyPreviews()
    {
        return $this->hasTinyPreviews;
    }

    /**
     * Whether a graph for the monitoring object exist
     *
     * @param   MonitoredObject $object
     *
     * @return  bool
     */
    abstract public function has(MonitoredObject $object);

    /**
     * Get a preview for the given object
     *
     * This function must return an empty string if no graph exists.
     *
     * @param   MonitoredObject $object
     *
     * @return  string
     * @throws  ProgrammingError
     *
     */
    public function getPreviewHtml(MonitoredObject $object)
    {
        throw new ProgrammingError('This hook provide previews but it is not implemented');
    }


    /**
     * Get a tiny preview for the given object
     *
     * This function must return an empty string if no graph exists.
     *
     * @param   MonitoredObject $object
     *
     * @return  string
     * @throws  ProgrammingError
     */
    public function getTinyPreviewHtml(MonitoredObject $object)
    {
        throw new ProgrammingError('This hook provide tiny previews but it is not implemented');
    }
}
