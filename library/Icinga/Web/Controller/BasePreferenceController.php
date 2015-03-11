<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

/**
 *  Base class for Preference Controllers
 *
 *  Module preferences use this class to make sure they are automatically
 *  added to the general preferences dialog. If you create a subclass of
 *  BasePreferenceController and overwrite @see init(), make sure you call
 *  parent::init(), otherwise you won't have the $tabs property in your view.
 *
 */
class BasePreferenceController extends ActionController
{
    /**
     * Return an array of tabs provided by this preference controller.
     *
     * Those tabs will automatically be added to the application's preference dialog
     *
     * @return array
     */
    public static function createProvidedTabs()
    {
        return array();
    }

    /**
     *  Initialize the controller and collect all tabs for it from the application and its modules
     *
     *  @see ActionController::init()
     */
    public function init()
    {
        parent::init();
        $this->view->tabs = ControllerTabCollector::collectControllerTabs('PreferenceController');
    }
}
