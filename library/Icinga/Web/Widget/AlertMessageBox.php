<?php

namespace Icinga\Web\Widget;

use \Zend_Log;
use \Zend_Form;
use \Icinga\User\Message;
use \Zend_View_Abstract;

/**
 * Class AlertMessageBox
 *
 * Displays a set of alert messages to the user.
 *
 * @package Icinga\Web\Widget
 */
class AlertMessageBox implements \Icinga\Web\Widget\Widget {

    /**
     * The displayed alert messages
     *
     * @var array
     */
    private $messages = array();

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
     * @param Message|array $messages     The message(s) to display
     */
    public function __construct($messages = array()) {
        if (!is_array($messages)) {
            $this->messages = array($messages);
        } else {
            $this->messages = $messages;
        }
    }

    /**
     * Add a new message.
     *
     * @param Message $message
     */
    public function addMessage(Message $message)
    {
        $this->messages[] = $message;
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
    public function render(Zend_View_Abstract $view = null) {
        $html = '';
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