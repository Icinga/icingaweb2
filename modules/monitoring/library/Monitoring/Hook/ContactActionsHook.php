<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use stdClass;

/**
 * Base class for contact action hooks
 */
abstract class ContactActionsHook extends BaseViewExtensionHook
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
     * Shall return the actions for the given contact (as valid HTML) in the format:
     *
     * array(
     *   array('GitHub', '<a href="https://github.com/Icinga">Icinga</a>'),
     *   array('Twitter', '<a href="https://www.twitter.com/icinga">icinga</a>')
     * )
     *
     * @param   stdClass    $contact    The contact to get actions for
     *
     * @return  string[][]
     */
    abstract public function getActions(stdClass $contact);
}
