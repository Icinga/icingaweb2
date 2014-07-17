<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Controller;

use Zend_Log;
use Icinga\Web\Session;
use Icinga\User\Message;
use Icinga\Authentication\Manager as AuthenticationManager;

/**
 *  Base class for Configuration Controllers
 *
 *  Module preferences use this class to make sure they are automatically
 *  added to the application's configuration dialog. If you create a subclass of
 *  BasePreferenceController and overwrite @see init(), make sure you call
 *  parent::init(), otherwise you won't have the $tabs property in your view.
 */
class BaseConfigController extends ActionController
{
    /**
     * Send a message with the logging level Zend_Log::INFO to the current user and
     * commit the changes to the underlying session.
     *
     * @param $msg      The message content
     */
    protected function addSuccessMessage($msg)
    {
        AuthenticationManager::getInstance()->getUser()->addMessage(
            new Message($msg, Zend_Log::INFO)
        );
        Session::getSession()->write();
    }

    /**
     * Send a message with the logging level Zend_Log::ERR to the current user and
     * commit the changes to the underlying session.
     *
     * @param $msg      The message content
     */
    protected function addErrorMessage($msg)
    {
        AuthenticationManager::getInstance()->getUser()->addMessage(
            new Message($msg, Zend_Log::ERR)
        );
        Session::getSession()->write();
    }

    /*
     * Return an array of tabs provided by this configuration controller.
     *
     * Those tabs will automatically be added to the application's configuration dialog
     *
     * @return array
     */
    public static function createProvidedTabs()
    {
        return array();
    }
}
