<?php

namespace Icinga\Web\Widget;

use \Zend_Log;
use \Zend_Form;
use \Zend_View_Abstract;
use Icinga\User;
use Icinga\User\Message;
use Icinga\Web\Session;
use Icinga\Authentication\Manager as AuthenticationManager;

/**
 * Displays a set of alert messages to the user.
 *
 * The messages are fetched automatically from the current AuthenticationManager,
 * but this is done lazily when render() is called, to ensure that messages will
 * always be displayed before they are cleared.
 */
class AlertMessageBox implements \Icinga\Web\Widget\Widget
{
    /**
     * Remove all messages from the current user, return them and commit
     * changes to the underlying session.
     *
     * @return array    The messages
     */
    protected function getAndClearMessages()
    {
        $messages = $this->user->getMessages();
        $this->user->clearMessages();
        Session::getSession()->write();
        return $messages;
    }

    /**
     * The displayed alert messages
     *
     * @var array
     */
    private $messages = array();

    /**
     * The user to fetch the messages from
     *
     * @var User
     */
    private $user;

    /**
     * The available states.
     *
     * @var array
     */
    private $states = array(
        Zend_Log::INFO => array(
            'state' => 'alert-success',
            'icon'  => 'icinga-icon-success'
        ),
        Zend_Log::NOTICE => array(
            'state' => 'alert-info',
            'icon'  => 'icinga-icon-info'
        ),
        Zend_Log::WARN => array(
            'state' => 'alert-warning',
            'icon'  => 'icinga-icon-warning'
        ),
        Zend_Log::ERR =>  array(
            'state' => 'alert-danger',
            'icon'  => 'icinga-icon-danger'
        )
    );

    /**
     * Create a new AlertBox
     *
     * @param boolean showUserMessages 	If the current user messages should be displayed
     *                                  in this AlertMessageBox. Defaults to false
     */
    public function __construct($showUserMessages = false)
    {
        if ($showUserMessages) {
            $this->user = AuthenticationManager::getInstance()->getUser();
        }
    }

    /**
     * Add a new error
     *
     * @param $error
     */
    public function addError($error)
    {
        $this->messages[] = new Message($error, Zend_Log::ERR);
    }

    /**
     * Add the error messages of the given Zend_Form
     */
    public function addForm(Zend_Form $form)
    {
        foreach ($form->getErrorMessages() as $error) {
            $this->addError($error);
        }
    }

    /**
     * Output the HTML of the AlertBox
     *
     * @return string
     */
    public function render(Zend_View_Abstract $view = null)
    {
        $html = '';
        if (isset($this->user)) {
            $this->messages = array_merge($this->messages, $this->getAndClearMessages());
        }
        foreach ($this->messages as $message) {
            $level = $message->getLevel();
            if (!array_key_exists($level, $this->states)) {
                continue;
            }
            $alert = $this->states[$level];
            $html .= '<div class="alert ' . $alert['state']. '">' .
                '<i class="' . $alert['icon'] . '"></i>' .
                '<strong>' . htmlspecialchars($message->getMessage()) . '</strong>' .
            '</div>';
        }
        return $html;
    }
}
